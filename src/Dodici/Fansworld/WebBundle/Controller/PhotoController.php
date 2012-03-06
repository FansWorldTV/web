<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Photo controller.
 * @Route("/photo")
 */
class PhotoController extends SiteController
{

    const LIMIT_PHOTOS = 8;

    /**
     * @Route("/{id}/{slug}", name= "photo_show", requirements = {"id" = "\d+"})
     * @Secure(roles="ROLE_USER")
     */
    public function showAction($id)
    {
        // TODO: photo show action, show photo + comments, allow commenting + answering root comments
        $photo = $this->getRepository('Photo')->findOneBy(array('id' => $id, 'active' => true));

        return new Response('ok');
    }

    /**
     * @Route("/upload", name="photo_upload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $privacies = Privacy::getOptions();

        $albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true));
        $albumchoices = array();
        foreach ($albums as $ab)
            $albumchoices[$ab->getId()] = $ab->getTitle();

        $photo = null;

        $defaultData = array();

        $collectionConstraint = new Collection(array(
                    'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'album' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($albumchoices))),
                    'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
                    'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
                    'file' => new \Symfony\Component\Validator\Constraints\Image()
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => true, 'label' => 'Título'))
                ->add('album', 'choice', array('required' => false, 'choices' => $albumchoices, 'label' => 'Album'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('file', 'file', array('required' => true, 'label' => 'Archivo'))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
                ->getForm();


        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $album = null;
                    if ($data['album']) {
                        $album = $this->getRepository('Album')->find($data['album']);
                        if (!$album || ($album && $album->getAuthor() != $user))
                            throw new \Exception('Invalid Album');
                    }

                    $mediaManager = $this->get("sonata.media.manager.media");

                    $media = new Media();
                    $media->setBinaryContent($data['file']);
                    $media->setContext('default'); // video related to the user
                    $media->setProviderName('sonata.media.provider.image');

                    $mediaManager->save($media);

                    $photo = new Photo();
                    $photo->setAuthor($user);
                    $photo->setAlbum($album);
                    $photo->setTitle($data['title']);
                    $photo->setContent($data['content']);
                    $photo->setImage($media);
                    $photo->setPrivacy($data['privacy']);
                    $em->persist($photo);
                    $em->flush();
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo foto'));
            }
        }

        return array('photo' => $photo, 'form' => $form->createView());
    }

    /**
     *  @Route("/ajax/get", name = "photo_get") 
     */
    public function ajaxGetPhotos()
    {
        $request = $this->getRequest();
        $userId = $request->get('userId');
        $page = (int) $request->get('page');

        $page--;
        $offset = $page * self::LIMIT_PHOTOS;

        $photos = $this->getRepository('Photo')->findBy(array('author' => $userId), array('createdAt' => 'DESC'), self::LIMIT_PHOTOS, $offset);

        $response = array();
        foreach ($photos as $photo) {
            $response['images'][] = array(
                'id' => $photo->getId(),
                'image' => $this->getImageUrl($photo->getImage()),
                'slug' => $photo->getSlug(),
                'title' => $photo->getTitle(),
                'comments' => $photo->getCommentCount()
            );
        }
        
        $countTotal = $this->getRepository('Photo')->countBy(array('author' => $userId));
        if ($countTotal > (($page+1) * self::LIMIT_PHOTOS)) {
            $response['gotMore'] = true;
        } else {
            $response['gotMore'] = false;
        }
        
        return $this->jsonResponse($response);
    }
}
