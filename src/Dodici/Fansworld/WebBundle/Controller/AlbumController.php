<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Album;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Form\FormError;

/**
 * Album controller.
 * @Route("/album")
 */
class AlbumController extends SiteController
{

    const LIMIT_ALBUMS = 8;

    /**
     * @Route("/{id}/{slug}", name= "album_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template()
     */
    public function showAction($id)
    {
        $album = $this->getRepository('Album')->find($id);
        
        return array(
            'album' => $album,
            'user' => $album->getAuthor()
        );
    }

    /**
     * @Route("/{id}", name= "album_comments", requirements = {"id" = "\d+"})
     */
    public function commentsAction($id)
    {
        // TODO
        return new Response('ok');
    }

    /**
     * @Route("/create", name="album_create")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function createAction()
    {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $privacies = Privacy::getOptions();

        $defaultData = array();

        $album = null;

        $collectionConstraint = new Collection(array(
                    'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
                    'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => true, 'label' => 'Título'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
                ->getForm();


        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $album = new Album();
                    $album->setAuthor($user);
                    $album->setTitle($data['title']);
                    $album->setContent($data['content']);
                    $album->setPrivacy($data['privacy']);
                    $em->persist($album);
                    $em->flush();
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error creando album'));
            }
        }

        return array('album' => $album, 'form' => $form->createView());
    }

    /**
     *  @Route("/ajax/get", name = "album_get") 
     */
    public function ajaxGetAlbums()
    {
        $request = $this->getRequest();
        $userId = $request->get('userId');
        $page = (int) $request->get('page');

        $page--;
        $offset = $page * self::LIMIT_ALBUMS;

        $albums = $this->getRepository('Album')->findBy(array('author' => $userId), array('createdAt' => 'DESC'), self::LIMIT_ALBUMS, $offset);

        $response = array();
        foreach ($albums as $album) {
            $response['albums'][] = array(
                'image' => $this->getImageUrl($album->getImage(), 'medium'),
                'id' => $album->getId(),
                'title' => $album->getTitle(),
                'countImages' => $album->getPhotoCount(),
                'comments' => $album->getCommentCount()
            );
        }

        $countTotal = $this->getRepository('Album')->countBy(array('author' => $userId));
        if ($countTotal > (($page+1) * self::LIMIT_ALBUMS)) {
            $response['gotMore'] = true;
        } else {
            $response['gotMore'] = false;
        }

        return $this->jsonResponse($response);
    }

}
