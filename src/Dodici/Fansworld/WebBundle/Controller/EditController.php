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

use Dodici\Fansworld\WebBundle\Entity\Idol;
use Dodici\Fansworld\WebBundle\Entity\Team;


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
	        $user = $this->getUser();
	        $em = $this->getDoctrine()->getEntityManager();

	        if (!$entity->getActive()) throw new \Exception('Entity has been deleted');

	        $refresh = false; $form = null;
			$prepopulate_info = '';
			$idEntity = $id;

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
	        		$defaultData['content'] = $entity->getContent();
	        	}

	        	if (property_exists($entity, 'privacy')) {
	        		$privacies = Privacy::getOptions();
	        		$constraints['privacy'] = array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies)));
	        		$fields['privacy'] = array('type' => 'choice', 'options' => array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'));
	        		$defaultData['privacy'] = $entity->getPrivacy();
	        	}

				$tagtext = '';
				$tagidol = '';
				$tagteam = '';
				$taguser = '';

	        	foreach($entity->getHasteams() as $hasteam) {
	        		$id = $hasteam->getTeam()->getId();
	        		$name = $hasteam->getTeam();
	        		$tagteam .= $id.',';
    	    		$prepopulate_info .= $id.':'.'team'.':'.$name.',';
    	    	}

    	    	foreach($entity->getHasidols() as $hasidol) {
    	    		$id = $hasidol->getIdol()->getId();
	        		$name = $hasidol->getIdol();
	        		$tagidol .= $id.',';
    	    		$prepopulate_info .= $id.':'.'idol'.':'.$name.',';
    	    	}

    	    	foreach($entity->getHasusers() as $hasuser) {
    	    		$id = $hasuser->getUser()->getId();
	        		$name = $hasuser->getUser();
	        		$taguser .= $id.',';
    	    		$prepopulate_info .= $id.':'.'user'.':'.$name.',';
    	    	}

		       	$constraints['tagtext'] = array();
		       	$fields['tagtext'] = array('type' => 'hidden', 'options' => array('required' => false));
		       	$defaultData['tagtext'] = $tagtext;

	            $constraints['tagidol'] = array();
	            $fields['tagidol'] = array('type' => 'hidden', 'options' => array('required' => false));
	            $defaultData['tagidol'] = $tagidol;

	            $constraints['tagteam'] = array();
	            $fields['tagteam'] = array('type' => 'hidden', 'options' => array('required' => false));
	            $defaultData['tagteam'] = $tagteam;

	            $constraints['taguser'] = array();
	            $fields['taguser'] = array('type' => 'hidden', 'options' => array('required' => false));
	            $defaultData['taguser'] = $taguser;

	           	$constraints['prepopulate'] = array();
	            $fields['prepopulate'] = array('type' => 'hidden', 'options' => array('required' => false));
	            $defaultData['prepopulate'] = $prepopulate_info;

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

		                    $tagtexts = explode(',', $data['tagtext']);
                    		$tagidols = explode(',', $data['tagidol']);
                    		$tagteams = explode(',', $data['tagteam']);
                    		$tagusers = explode(',', $data['taguser']);
                    		$this->_tagEntity($tagtexts, $tagidols, $tagteams, $tagusers, $user, $entity);

                            //test commit
							
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

	        return array('form' => $form ? $form->createView() : null, 'refresh' => $refresh, 'json_prepopulate' => $prepopulate_info, 'id' => $idEntity, 'type' => $type);

        } catch (\Exception $e) {
        	return new Response($e->getMessage(), 400);
        }
    }


    private function _tagEntity ($tagtexts, $tagidols, $tagteams, $tagusers, $user, $entity) {
        $idolrepo = $this->getRepository('Idol');
        $teamrepo = $this->getRepository('Team');
        $userrepo = $this->getRepository('User');
        $tagitems = array();

        foreach ($tagtexts as $eText) {
            if (trim($eText))
                $tagitems[] = $eText;
        }

        foreach ($tagidols as $eIdol) {
            $idolEntity = $idolrepo->find($eIdol);
            if ($idolEntity)
                $tagitems[] = $idolEntity;
        }

        foreach ($tagteams as $eTeam) {
            $teamEntity = $teamrepo->find($eTeam);
            if ($teamEntity)
                $tagitems[] = $teamEntity;
        }

        foreach ($tagusers as $eUser) {
            $userEntity = $userrepo->find($eUser);
            if ($userEntity)
                $tagitems[] = $userEntity;
        }

        if (!empty($tagitems))
            $this->get('tagger')->tag($user, $entity, $tagitems);
    }

}