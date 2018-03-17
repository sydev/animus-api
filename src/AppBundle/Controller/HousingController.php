<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Housing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class HousingController extends Controller
{
    /**
     * @Route("/housings", methods={"GET"})
     */
    public function getHousingsAction(Request $request) {
        $email      = $request->query->get('email');
        $repository = $this->getDoctrine()->getRepository(Housing::class);
        $housings   = ($email !== null) ? $repository->findBy(['email' => filter_var($email, FILTER_VALIDATE_EMAIL)]) : $repository->findAll();
        $response   = new JsonResponse($housings);
        
        return $response;
    }

    /**
     * @Route("/housing", methods={"POST"})
     */
    public function addHousingAction(Request $request /*, \Swift_Mailer $mailer*/) {
        $data           = (object) json_decode($request->getContent(), true);
        $entityManager  = $this->getDoctrine()->getManager();

        $housing = new Housing();
        $housing->setEnterDate(new \DateTime($data->enterDate));
        $housing->setStreet($data->street);
        $housing->setZipCode($data->zipCode);
        $housing->setCity($data->city);
        $housing->setCountry($data->country);
        $housing->setEmail($data->email);
        $housing->setToken(bin2hex(random_bytes(18)));

        $entityManager->persist($housing);
        $entityManager->flush();

        /*$mailLogger = new \Swift_Plugins_Loggers_ArrayLogger();
        $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($mailLogger));

        $message = (new \Swift_Message('HELLO'))
            ->setFrom('info@animus.de')
            ->setTo($data->email)
            ->setBody('Test', 'text/plain');

        $sent = $mailer->send($message);*/

        // Use mail() instead of SwiftMailer, because I couldn´t make it work locally
        // And I did not have the time to thoroughly debug there
        $token      = $housing->getToken();
        $id         = $housing->getId();
        $base_url   = $this->getParameter('frontend_url');
        $url        = "$base_url/housing/$id?token=$token";
        $sent       = mail($data->email, 'Animus App', $url, "From: info@animus.de\r\nReply-To: info@animus.de\r\n");

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
     * @Route("/housing/{id}", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function updateHousingAction(Request $request, $id) {
        $entityManager  = $this->getDoctrine()->getManager();
        $repository     = $this->getDoctrine()->getRepository(Housing::class);
        $housing        = $repository->findOneById($id);

        if (!$housing) throw $this->createNotFoundException("No housing found for id `$id`");

        $data = (object) json_decode($request->getContent());

        $housing->setEnterDate(new \DateTime($data->enterDate));
        $housing->setStreet($data->street);
        $housing->setZipCode($data->zipCode);
        $housing->setCity($data->city);
        $housing->setCountry($data->country);
        $housing->setEmail($data->email);

        $entityManager->flush();

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
}
