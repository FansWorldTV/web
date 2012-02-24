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
    
    /**
     * @Route("/show/{id}/{slug}", name= "photo_show", requirements = {"id" = "\d+"})
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

        $photo = null;

        $defaultData = array();

        $collectionConstraint = new Collection(array(
                    'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
        			'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250)),
                    'file' => new \Symfony\Component\Validator\Constraints\File()
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => true, 'label' => 'Título'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('file', 'file', array('required' => true, 'label' => 'Archivo'))
                ->getForm();


        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();
                
                if ($form->isValid()) {
                    $mediaManager = $this->get("sonata.media.manager.media");
					
					$media = new Media();
				    $media->setBinaryContent($data['file']);
				    $media->setContext('default'); // video related to the user
				    $media->setProviderName('sonata.media.provider.image');
				
				    $mediaManager->save($media);
				    
				    $photo = new Photo();
				    $photo->setAuthor($user);
				    $photo->setTitle($data['title']);
				    $photo->setContent($data['content']);
				    $photo->setImage($media);
				    $em->persist($photo);
				    $em->flush();
				    
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo foto'));
            }
        }

        return array('photo' => $photo, 'form' => $form->createView());
    }
}
