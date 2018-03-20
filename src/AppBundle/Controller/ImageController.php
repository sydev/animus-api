<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Image;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImageController extends Controller
{

  /**
   * @Route("/images", methods={"GET"})
   */
  public function getImagesAction(Request $request) {
    $ids = $request->query->get('ids');
    if ($ids === null) return new JsonResponse(['error' => 'Parameter `ids` is required'], 422);

    $ids          = explode(',', $ids);
    $repository   = $this->getDoctrine()->getRepository(Image::class);
    $queryBuilder = $repository->createQueryBuilder('i');

    $queryBuilder->where($queryBuilder->expr()->in('i.id', $ids));

    $result = $queryBuilder->getQuery()->getResult();

    return new JsonResponse($result);
  }

  /**
   * @Route("/image", methods={"POST"})
   */
  public function addImageAction(Request $request) {
    $root       = dirname($this->container->get('kernel')->getRootDir());
    $uploadDir  = $root .'/web/uploads';
    $uploadUrl  = $request->getSchemeAndHttpHost() .'/uploads';
    $errors     = [];
    $images     = [];
    $em         = $this->getDoctrine()->getManager();
    $files      = $request->files->get('images');
    if (!$files) return new JsonResponse(['error' => '`images` is required'], 422);

    foreach ($files as $file) {
      if (!$file->isValid()) $errors[] = ['error' => $file->getError(), 'code' => 422];

      $mimeType = $file->getClientMimeType();
      $fileType = explode('/', $mimeType)[0];
      if ($fileType !== 'image') $errors[] = ['error' => 'File must be an image', 'code' => 422];

      $fileName = explode('.', $file->getClientOriginalName())[0] .'_'. time() .'.'. $file->getClientOriginalExtension();
      $file->move($uploadDir, $fileName);

      $fileUrl  = $uploadUrl .'/'. $fileName;
      $image    = new Image();

      $image->setUrl($fileUrl);
      $image->setName($file->getClientOriginalName());
      $image->setSize($file->getClientSize());

      $em->persist($image);
      $em->flush();

      $images[] = $image->jsonSerialize();
    }
     
    return (empty($errors)) ? new JsonResponse($images) : new JsonResponse($errors[0]['error'], $errors[0]['code']);
  }

  /**
   * @Route("/image/{id}", methods={"GET"}, requirements={"id"="\d+"})
   */
  public function getImageAction(Request $request, $id) {
    $repository = $this->getDoctrine()->getRepository(Image::class);
    $image      = $repository->findOneById($id);

    if (!$image) throw $this->createNotFoundException("No image found for id `$id`");

    return new JsonResponse($image);
  }

  /**
   * @Route("/image/{id}", methods={"DELETE"}, requirements={"id"="\d+"})
   */
  public function deleteImageAction(Request $request, $id) {
    $em         = $this->getDoctrine()->getManager();
    $repository = $this->getDoctrine()->getRepository(Image::class);
    $image      = $repository->findOneById($id);

    if (!$image) throw $this->createNotFoundException("No image found for id `$id`");

    $em->remove($image);
    $em->flush();

    return new JsonResponse(['deleted' => true]);
  }
}
