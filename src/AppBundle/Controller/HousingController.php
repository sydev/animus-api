<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Housing;
use AppBundle\Entity\Image;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;

use DateTime;

use Swift_Plugins_Loggers_ArrayLogger;
use Swift_Plugins_LoggerPlugin;


class HousingController extends Controller
{
    /**
     * @Route("/housings", methods={"GET"})
     */
    public function getHousingsAction(Request $request) {
        $limit  = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $repository = $this->getDoctrine()->getRepository(Housing::class);
        $total      = $repository->createQueryBuilder('h')->select('count(h.id)')->getQuery()->getSingleScalarResult();
        $housings   = $repository->findBy([], [], intval($limit), intval($offset));
        
        return new JsonResponse($housings, 200, ['X-Total' => $total]);
    }

    /**
     * @Route("/housing", methods={"POST"})
     */
    public function addHousingAction(Request $request) {
        $images = $this->handleFileUpload($request);
        var_dump($images); die;
        
        try {
            

            // Throw any error occurred while file upload
            foreach ($images as $image) {
                if ($image instanceof Error) throw $image;
            }
        } catch (Exception $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }

        $em         = $this->getDoctrine()->getManager();
        $email      = $request->request->get('email');
        $housing    = new Housing();

        $housing->setEnterDate(new DateTime($request->request->get('enterDate')));
        $housing->setStreet($request->request->get('street'));
        $housing->setZipCode($request->request->get('zipCode'));
        $housing->setCity($request->request->get('city'));
        $housing->setCountry($request->request->get('country'));
        $housing->setEmail($email);
        $housing->setToken(bin2hex(random_bytes(18)));

        foreach ($images as $image) $housing->addImage($image);

        $validator  = $this->get('validator');
        $errors     = $validator->validate($housing);
        if (count($errors) > 0) return new JsonResponse(['param' => $errors[0]->getPropertyPath(), 'message' => $errors[0]->getMessage()], 422);

        $em->persist($housing);
        $em->flush();

        $mailer = $this->get('mailer');
        if ($mailer === null) return new JsonResponse(['error' => 'Housing created, but no mail is sent']);

        // Send mail
        $mailLogger = new Swift_Plugins_Loggers_ArrayLogger();
        $mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($mailLogger));

        $token      = $housing->getToken();
        $id         = $housing->getId();
        $base_url   = $this->getParameter('frontend_url');
        $url        = "$base_url/housing/$id?token=$token";
        $message    = (new \Swift_Message('Wohnung erstellt'))
            ->setFrom('info@sydev.de')
            ->setTo($email)
            ->setBody("Hallo,\n\nDu hast erfolgreich eine Wohnung eingetragen. Diese kannst du jetzt unter diesem Link bearbeiten:\n\n$url", 'text/plain');

        $mailer->send($message);

        return new JsonResponse(['created' => true, 'housing' => $housing]);
    }

    /**
     * @Route("/housing/{id}", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getHousingAction(Request $request, $id) {
        $repository = $this->getDoctrine()->getRepository(Housing::class);
        $housing    = $repository->findOneById($id);

        if (!$housing) throw $this->createNotFoundException("No housing found for id `$id`");

        return new JsonResponse($housing);
    }

    /**
     * @Route("/housing/{id}", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function updateHousingAction(Request $request, $id) {
        try {
            $new_images = $this->handleFileUpload($request);

            // Throw any error occurred while file upload
            foreach ($new_images as $image) {
                if ($image instanceof Error) throw $image;
            }
        } catch (Exception $e) {
            return new JsonResponse($e->getMessage(), $e->getCode());
        }

        $em         = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Housing::class);
        $housing    = $repository->findOneById($id);

        if (!$housing) throw $this->createNotFoundException("No housing found for id `$id`");

        $data = (object) $request->request->all();

        $housing->setEnterDate(new \DateTime($data->enterDate));
        $housing->setStreet($data->street);
        $housing->setZipCode($data->zipCode);
        $housing->setCity($data->city);
        $housing->setCountry($data->country);
        $housing->setEmail($data->email);

        // Remove images which aren´t represented in `keep_images`
        $keep_images    = (isset($data->keep_images)) ? array_map(function($id) { return intval($id); }, $data->keep_images) : [];
        $current_images = $housing->getImages()->toArray();

        foreach ($current_images as $image) {
            if (!in_array($image->getId(), $keep_images)) $housing->removeImage($image);
        }

        // Add new images
        foreach ($new_images as $image) $housing->addImage($image);

        // Update tables
        $em->flush();

        return new JsonResponse(['updated' => true, 'housing' => $housing]);
    }

    /**
     * @Route("/housing/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function deleteHousingAction(Request $request, $id) {
        $entityManager  = $this->getDoctrine()->getManager();
        $repository     = $this->getDoctrine()->getRepository(Housing::class);
        $housing        = $repository->findOneById($id);

        if (!$housing) throw $this->createNotFoundException("No housing found for id `$id`");

        $entityManager->remove($housing);
        $entityManager->flush();

        return new JsonResponse(['deleted' => true]);
    }





    /**
     * Handle uploaded files
     *
     * @param FileBag $files
     * @return Image[]|Error[]
     */
    private function handleFileUpload(Request $request) {
        $files = $request->files->get('images');
        if (!$files) return [];

        $root       = dirname($this->container->get('kernel')->getRootDir());
        $uploadDir  = $root .'/web/uploads';
        $uploadUrl  = $request->getSchemeAndHttpHost() .'/uploads';
        $errors     = [];
        $images     = [];
        $em         = $this->getDoctrine()->getManager();

        foreach ($files as $file) {
            // Check if $file is valid
            if (!$file->isValid()) {
                $errors[] = $file->getError();
                continue;
            }
      
            // Check if $file is an image
            $mimeType = $file->getClientMimeType();
            $fileType = explode('/', $mimeType)[0];

            if ($fileType !== 'image') {
                $errors[] = new Error('File must be an image', 422);
                continue;
            }

            // Move uploaded file from temp dir to $uploadDir
            $fileName = explode('.', $file->getClientOriginalName())[0] .'_'. time() .'.'. $file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);
      
            // Create the file url
            $fileUrl  = $uploadUrl .'/'. $fileName;

            // Initialize a new Image and set the properties
            $image = new Image();
            $image->setUrl($fileUrl);
            $image->setName($fileName);
            $image->setSize($file->getClientSize());
      
            $images[] = $image;
          }
           
          // Return the images if there are no errors
          return (empty($errors)) ? $images : $errors;
    }
}
