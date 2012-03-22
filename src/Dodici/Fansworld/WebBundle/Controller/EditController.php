<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\ForumPost;

use Symfony\Component\Validator\Constraints\NotBlank;

use Dodici\Fansworld\WebBundle\Entity\Privacy;

use Symfony\Component\Validator\Constraints\Collection;

use Symfony\Component\Form\FormError;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Dodici\Fansworld\WebBundle\Entity\Album;
use Symfony\Component\HttpFoundation\Request;

/**
 * Edit controller.
 * @Route("/edit")
 */
class EditController extends SiteController
{

    /**
     * 
     * @Route("/popup/{type}/{id}", name="edit_popup")
     * @Template
     */
    public function popupAction($type, $id)
    {
        try {
	    	$request = $this->getRequest();
	        $translator = $this->get('translator');
	        $appstate = $this->get('appstate');
	        
	        if (!in_array($type, array('photo','video','album','forumpost','comment')))
	        throw new \Exception('Invalid type');
	        
	        $repo = $this->getRepository($type);
	        $entity = $repo->find($id);
	        $user = $this->get('security.context')->getToken()->getUser();
	        $em = $this->getDoctrine()->getEntityManager();
	        
	        if (!$entity->getActive()) throw new \Exception('Entity has been deleted'); 
	        
	        $refresh = false; $form = null;
	        
	        if ($appstate->canEdit($entity)) {
	        	$constraints = array();
	        	$fields = array();
	        	$defaultData = array();
	        	
	        	if (property_exists($entity, 'title')) {
	        		$constraints['title'] = array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250)));
	        		$fields['title'] = array('type' => 'text', 'options' => array('required' => true, 'label' => 'Título'));
	        		$defaultData['title'] = $entity->getTitle();
	        	}
	        	
	        	if (property_exists($entity, 'album')) {
	        		$albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true));
			        $albumchoices = array();
			        foreach ($albums as $ab)
			            $albumchoices[$ab->getId()] = $ab->getTitle();
			            
			        $albumchoices['NEW'] = '+ (NUEVO)';
	        		
	        		$constraints['album'] = array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($albumchoices)));
	        		$fields['album'] = array('type' => 'choice', 'options' => array('required' => true, 'choices' => $albumchoices, 'label' => 'Album'));
	        		$defaultData['album'] = $entity->getAlbum()->getId();
	        	}
	        	
	        	if (property_exists($entity, 'content')) {
	        		$constraints['content'] = new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400));
	        		$fields['content'] = array('type' => 'textarea', 'options' => array('required' => false, 'label' => 'Descripción'));
	        		$defaultData['content'] = $entity->getTitle();
	        	}
	        	
	        	if (property_exists($entity, 'privacy')) {
	        		$privacies = Privacy::getOptions();
	        		$constraints['privacy'] = array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies)));
	        		$fields['privacy'] = array('type' => 'choice', 'options' => array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'));
	        		$defaultData['privacy'] = $entity->getPrivacy();
	        	}
	        	
	        	$collectionConstraint = new Collection($constraints);
				$form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint));
				foreach ($fields as $key => $field) {
					$form = $form->add($key, $field['type'], $field['options']);
				} 
				$form = $form->getForm();
		        		
		        if ($request->getMethod() == 'POST') {
		            try {
		                $form->bindRequest($request);
		                $data = $form->getData();
		
		                if ($form->isValid()) {
		                    $album = null;
		                    if (property_exists($entity, 'album') && $data['album']) {
		                        if ($data['album'] == 'NEW') {
		                        	$albumtitle = $request->get('album_new_name');
		                        	if (!$albumtitle) throw new \Exception('Enter an Album Title');
		                        	$album = new Album();
		                        	$album->setTitle($albumtitle);
		                        	$album->setAuthor($user);
		                        	$album->setPrivacy($data['privacy']);
		                        	$em->persist($album);
		                        } else {
			                    	$album = $this->getRepository('Album')->find($data['album']);
			                        if (!$album || ($album && $album->getAuthor() != $user))
			                            throw new \Exception('Invalid Album');
		                        }
		                    }
		                    
		                    foreach ($data as $key => $val) {
		                    	if (property_exists($entity, $key)) {
		                    		if ($key == 'album') {
		                    			$entity->setAlbum($album);
		                    		} else {
			                    		$methodname = 'set'.ucfirst($key);
			                    		$entity->$methodname($val);
		                    		}
		                    	}
		                    }
		                    $em->persist($entity);
		                    $em->flush();
		                    		                    
		                    $this->get('session')->setFlash('success', 'Has realizado tu modificación con éxito');
		                    
		                    
		                    
		                    if ($entity instanceof ForumPost) {
		                    	$thread = $entity->getForumThread();
		                    	$refresh = $this->generateUrl('forum_thread', array('id' => $thread->getId(),'slug' => $thread->getSlug()));
		                    } elseif ($entity instanceof Comment) {
		                    	$refresh = $request->getScheme().'://'.$request->getHost().$_SERVER['REQUEST_URI']; 
		                    } else {
		                    	$refresh = $this->generateUrl($type.'_show', array('id' => $entity->getId(),'slug' => $entity->getSlug()));
		                    }
		                }
		            } catch (\Exception $e) {
		                $form->addError(new FormError($e->getMessage()));
		            }
		        }
		
	        } else {
	        	if (!($user instanceof User)) {
	        		throw new \Exception('User not logged in');
	        	} else {
	        		throw new \Exception('User cannot edit entity');
	        	}
	        }
	
	        return array('form' => $form ? $form->createView() : null, 'refresh' => $refresh);
	        
        } catch (\Exception $e) {
        	return new Response($e->getMessage(), 400);
        }
    }

}
