<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Application\Sonata\UserBundle\Entity\User;

use Dodici\Fansworld\WebBundle\Entity\Album;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
    const LIMIT_PHOTOS_PIN = 9;

    /**
     * @Route("/{id}/{slug}", name= "photo_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template()
     */
    public function showAction($id)
    {
        $repo = $this->getRepository('Photo');
        $photo = $repo->findOneBy(array('id' => $id, 'active' => true));

        $next = $repo->getNextActive($id);
        $prev = $repo->getPrevActive($id);
        
        $this->securityCheck($photo);
        
        return array(
            'photo' => $photo,
            'prev' => $prev,
            'next' => $next
            );
    }
    
    /**
     * @Route("/", name= "photo_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function listAction()
    {
        return array(
            
            );
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
            
        $albumchoices['NEW'] = '+ (NUEVO)';

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
                ->add('album', 'choice', array('required' => true, 'choices' => $albumchoices, 'label' => 'Album'))
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
                    
                    $this->get('session')->setFlash('success', '¡Has subido una foto con éxito!');
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
        if ($userId == 'null') $userId = null;
        $page = (int) $request->get('page');
        $renderpin = $request->get('renderpin', false);
        
        $limit = $renderpin ? self::LIMIT_PHOTOS_PIN : self::LIMIT_PHOTOS;

        $page--;
        $offset = $page * $limit;
        
        $params = array();
        if ($userId) $params['author'] = $userId;
        $params['active'] = true;

        $photos = $this->getRepository('Photo')->findBy($params, array('createdAt' => 'DESC'), $limit, $offset);

        $response = array();
        foreach ($photos as $photo) {
            if ($renderpin) {
            	$response['images'][] = array(
            		'htmlpin' => $this->renderView('DodiciFansworldWebBundle:Default:pin.html.twig', array('entity' => $photo))
            	);
            } else {
	        	$response['images'][] = array(
	                'id' => $photo->getId(),
	                'image' => $this->getImageUrl($photo->getImage()),
	                'slug' => $photo->getSlug(),
	                'title' => $photo->getTitle(),
	                'comments' => $photo->getCommentCount()
	            );
            }
        }

        $countTotal = $this->getRepository('Photo')->countBy($params);
        if ($countTotal > (($page + 1) * $limit)) {
            $response['gotMore'] = true;
        } else {
            $response['gotMore'] = false;
        }

        return $this->jsonResponse($response);
    }

}
