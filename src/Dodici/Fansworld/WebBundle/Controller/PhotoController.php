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
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;

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
        $user = $this->getRepository('User')->find($photo->getAuthor()->getId());
        

        $next = $repo->getNextActive($id, $photo->getAuthor(), $photo->getAlbum());
        $prev = $repo->getPrevActive($id, $photo->getAuthor(), $photo->getAlbum());

        $this->securityCheck($photo);

        $this->get('visitator')->visit($photo);
        return array(
            'photo' => $photo,
            'prev' => $prev,
            'next' => $next,
            'user' => $user
        );
    }

    /**
     * @Route("", name= "photo_list")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function listAction()
    {
        return array();
    }

    /**
     * @Route("/upload", name="photo_upload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        $idolToTagId = $request->get('idolToTag', false);
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $privacies = Privacy::getOptions();

        $idolToTag = $this->getRepository('Idol')->find($idolToTagId);

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
                    'file' => new \Symfony\Component\Validator\Constraints\Image(),
                    'tagtext' => array(),
                    'taguser' => array()
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => true, 'label' => 'Títuloooooooooooooooooooooooooooo'))
                ->add('album', 'choice', array('required' => true, 'choices' => $albumchoices, 'label' => 'Album'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('file', 'file', array('required' => true, 'label' => 'Archivo'))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
                ->add('tagtext', 'hidden', array('required' => false))
                ->add('taguser', 'hidden', array('required' => false))
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
                            if (!$albumtitle)
                                throw new \Exception('Enter an Album Title');
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

                    $tagtexts = explode(',', $data['tagtext']);
                    $tagusers = explode(',', $data['taguser']);
                    $userrepo = $this->getRepository('User');
                    $tagitems = array();

                    foreach ($tagtexts as $tt) {
                        if (trim($tt))
                            $tagitems[] = $tt;
                    }
                    foreach ($tagusers as $tu) {
                        $tuser = $userrepo->find($tu);
                        if ($tuser)
                            $tagitems[] = $tuser;
                    }

                    if (!empty($tagitems))
                        $this->get('tagger')->tag($user, $photo, $tagitems);

                    $this->get('session')->setFlash('success', $this->trans('upload_sucess'));
                }
            } catch (\Exception $e) {
                $form->addError(new FormError($this->trans('upload_error')));
            }
        }

        return array('photo' => $photo, 'form' => $form->createView(), 'idolToTag' => $idolToTag);
    }

    /**
     *  @Route("/ajax/get", name = "photo_get") 
     */
    public function ajaxGetPhotos()
    {
        $request = $this->getRequest();
        $userId = $request->get('userId');
        if ($userId == 'null')
            $userId = null;
        $page = (int) $request->get('page');
        $renderpin = $request->get('renderpin', false);

        $limit = $renderpin ? self::LIMIT_PHOTOS_PIN : self::LIMIT_PHOTOS;
        $page--;
        $offset = $page * $limit;

        $params = array();
        if ($userId)
            $params['author'] = $userId;
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

    /**
     * @Route("/fileupload", name="photo_fileupload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fileUploadAction()
    {
        $request = $this->getRequest();

        if ($request->getMethod() == 'POST') {
            $originalFileName = $request->get('qqfile');
            $originalFile = substr($originalFileName, 0, -4);
            $ext = substr($originalFileName, -3);

            $response = array('error' => 'Could not save uploaded file.' . 'The upload was cancelled, or server error encountered');
            $image = null;

            $input = fopen("php://input", "r");
            $imagecontent = stream_get_contents($input);
            $tempFile = tempnam("uploads/temp", 'IMG');
            file_put_contents($tempFile, $imagecontent);
            fclose($input);

            if (file_exists($tempFile)) {
                $pathinfo = pathinfo($tempFile);
                $filename = $pathinfo['filename'];
                $ext = $pathinfo['extension'];
                $tmpFileName =  $filename . '.' . $ext;
                $response = array('success' => true, 'tempFile' => $tmpFileName, 'originalFile' => $originalFile, 'ext' => $ext);
            } else {
                $response = array('success' => false, 'tempFile' => '');
            }
            return $this->jsonResponse($response);
        }
        return array();
    }

    /**
     * @Route("/fileupload/{tempFile}/{originalFile}/{ext}", name="photo_filemeta")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fileMetaAction($tempFile, $originalFile, $ext)
    {
        $request = $this->getRequest();
        $redirectColorBox = false;
    
        $user = $this->getUser();
        $photo = new Photo();
        $idolToTag = "";
        $em = $this->getDoctrine()->getEntityManager();
        
        $form = $this->_createForm($user->getId());

        if ($request->getMethod() == 'POST') {

            try {

                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $album = null;
                    if ($data['album']) {
                        $album = $this->_createAlbum($data['album'], $request->get('album_new_name'), $user, $data['privacy'], $em);
                    }

                    $cropOptions = array(
                        "cropX" => $data['x'],
                        "cropY" => $data['y'],
                        "cropW" => $data['w'],
                        "cropH" => $data['h'],
                        "tempFile" => $tempFile,
                        "originalFile" => $originalFile,
                        "extension" => $ext
                    );
                    $media = $this->_GenerateMediaCrop($cropOptions);

                    $photo->setImage($media);
                    $photo->setAuthor($user);
                    $photo->setAlbum($album);
                    $photo->setTitle($data['title']);
                    $photo->setContent($data['content']);
                    $photo->setPrivacy($data['privacy']);
                    $em->persist($photo);
                    $em->flush();

                    $tagtexts = explode(',', $data['tagtext']);
                    $tagidols = explode(',', $data['tagidol']);
                    $tagteams = explode(',', $data['tagteam']);
                    $tagusers = explode(',', $data['taguser']);
                    $this->_tagEntity($tagtexts, $tagidols, $tagteams, $tagusers, $user, $photo);
                  
                    function toBoolean(&$var) {$var = $var == 'true' ? true : false;}
                    $shareEntities = array(
                        "idols" => $data['shareidol'],
                        "teams" => $data['shareteam'],
                        "users" => $data['shareuser']
                    );
                    $this->_sharePhoto($photo, toBoolean($data['fb']), toBoolean($data['tw']), toBoolean($data['fw']), $data['title'], $shareEntities);
                    
                    $this->get('session')->setFlash('success', $this->trans('upload_sucess'));
                    $redirectColorBox = true;
                }
            } catch (\Exception $e) {
                $form->addError(new FormError($this->trans('upload_error')));
            }
        }
        return array('photo' => $photo, 'form' => $form->createView(), 'idolToTag' => $idolToTag, 'tempFile' => $tempFile, 'originalFile' => $originalFile, 'ext' => $ext, 'redirectColorBox' => $redirectColorBox);
    }

    private function _createForm ($userId) {
        $privacies = Privacy::getOptions();
        $albums = $this->getRepository('Album')->findBy(array('author' => $userId, 'active' => true));
        $albumchoices = array();
        foreach ($albums as $ab)
            $albumchoices[$ab->getId()] = $ab->getTitle();
        $albumchoices['NEW'] = '+ (NUEVO)';
        $defaultData = array();
        $collectionConstraint = new Collection(array(
            'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
            'album' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($albumchoices))),
            'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
            'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
            'tagtext' => array(),
            'tagidol' => array(),
            'tagteam' => array(),
            'taguser' => array(),
            'shareteam' => array(),
            'shareidol' => array(),
            'shareuser' => array(),
            'fb' => array(),
            'tw' => array(),
            'fw' => array(),
            'x' => array(),
            'y' => array(),
            'w' => array(),
            'h' => array()
        ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
            ->add('title', 'text', array('required' => true, 'label' => 'Título'))
            ->add('album', 'choice', array('required' => true, 'choices' => $albumchoices, 'label' => 'Album'))
            ->add('content', 'textarea', array('required' => false,'label' => 'Descripción'))
            ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
            ->add('tagtext', 'hidden', array('required' => false))
            ->add('tagidol', 'hidden', array('required' => false))
            ->add('tagteam', 'hidden', array('required' => false))
            ->add('taguser', 'hidden', array('required' => false))
            ->add('shareteam', 'hidden', array('required' => false))
            ->add('shareidol', 'hidden', array('required' => false))
            ->add('shareuser', 'hidden', array('required' => false))
            ->add('fb', 'hidden', array('required' => false))
            ->add('tw', 'hidden', array('required' => false))
            ->add('fw', 'hidden', array('required' => false))
            ->add('x', 'hidden', array('required' => false, 'data' => 0))
            ->add('y', 'hidden', array('required' => false, 'data' => 0))
            ->add('w', 'hidden', array('required' => false, 'data' => 0))
            ->add('h', 'hidden', array('required' => false, 'data' => 0))
            ->getForm();
        return $form;
    }

    private function _createAlbum($value, $title, $user, $privacy, $entityManager) {
        if ($value == 'NEW') {
            if (!$title)
                throw new \Exception('Enter an Album Title');
            $album = new Album();
            $album->setTitle($title);
            $album->setAuthor($user);
            $album->setPrivacy($privacy);
            $entityManager->persist($album);
        } else {
            $album = $this->getRepository('Album')->find($value);
            if (!$album || ($album && $album->getAuthor() != $user))
                throw new \Exception('Invalid Album');
        }
        return $album;
    }

    private function _GenerateMediaCrop(array $options) {
        $imagine = new Imagine();

        if (0 == $options['cropW'] || 0 ==   $options['cropH']) {
            $imageStream = $imagine->open("uploads/temp/".$options['tempFile']);
        } else {
            $imageStream = $imagine->open("uploads/temp/".$options['tempFile'])
            ->crop(new Point($options['cropX'], $options['cropY']), new Box($options['cropW'], $options['cropH']));
        }

        $metaData = array('name' => $options['originalFile'].'.'.$options['extension']);
        return $this->get('appmedia')->createImageFromBinary($imageStream, $metaData);
    }

    private function _tagEntity ($tagtexts, $tagidols, $tagteams, $tagusers, $user, $photo) {
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
            $this->get('tagger')->tag($user, $photo, $tagitems);
    }

    private function _sharePhoto ($photo, $toFb, $toTw, $toFw, $shareMessage, $entities) {
        $idolrepo = $this->getRepository('Idol');
        $teamrepo = $this->getRepository('Team');
        $userrepo = $this->getRepository('User');
        $response = array('error' => false, 'msg' => 'Sent...');

        if ($this->getUser() instanceof User) {
            if ($toFb) {
                $facebook = $this->get('app.facebook');
                $facebook instanceof AppFacebook;
                try {
                    $facebook->entityShare($photo, $shareMessage);
                } catch (\Exception $exc) {
                    $response['error'] = true;
                    $response['msg'] = $exc->getMessage();
                }
            }

            if ($toTw) {
                $twitter = $this->get('app.twitter');
                $twitter instanceof AppTwitter;
                try {
                    $twitter->entityShare($photo, $shareMessage);
                } catch (\Exception $exc) {
                    $response['error'] = true;
                    $response['msg'] = $exc->getMessage();
                }
            }

            if ($toFw) {
                $shareEntities = array();
                $shareidols = explode(',', $entities['idols']);
                $shareteams = explode(',', $entities['teams']);
                $shareusers = explode(',', $entities['users']);

                foreach ($shareidols as $eIdol) {
                    $idolEntity = $idolrepo->find($eIdol);
                    if ($idolEntity)
                        $shareEntities[] = $idolEntity;
                }

                foreach ($shareteams as $eTeam) {
                    $teamEntity = $teamrepo->find($eTeam);
                    if ($teamEntity)
                        $shareEntities[] = $teamEntity;
                }

                foreach ($shareusers as $eUser) {
                    $userEntity = $userrepo->find($eUser);
                    if ($userEntity)
                        $shareEntities[] = $userEntity;
                }

                if (!empty($shareEntities)) {
                    $sharer = $this->get('sharer');
                    $sharer->share($photo, $shareEntities, $shareMessage, $this->getUser());
                }
            }
        } else {
            $response['error'] = true;
            $response['msg'] = 'User is not logged';
        }
    }
}