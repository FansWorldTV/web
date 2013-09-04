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
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Album controller.
 * @Route("/album")
 */
class AlbumController extends SiteController
{

    const LIMIT_ALBUMS = 8;

    /**
     * @Route("/create", name="album_create")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function createAction()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $privacies = Privacy::getOptions();

        $defaultData = array();
        $album = null;
        $refresh = null;
        $juan = null;

        $collectionConstraint = new Collection(array(
                    'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
                    'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => true, 'label' => $this->trans('Title')))
                ->add('content', 'textarea', array('required' => false, 'label' => $this->trans('Description')))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => $this->trans('Privacy')))
                ->getForm();


        if ($request->getMethod() == 'POST') {

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

                    $this->get('session')->setFlash('success', 'El album ha sido creado');
                    $refresh = $this->generateUrl('user_showalbum', array('id' => $album->getId(), 'username' => $user->getUsername()));
                }

        }

        return array('album' => $album, 'form' => $form->createView(), 'refresh' => $refresh);
    }
}
