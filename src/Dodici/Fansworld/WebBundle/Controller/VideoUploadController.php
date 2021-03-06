<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\MediaBundle\Entity\Media;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Form\FormError;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Dodici\Fansworld\WebBundle\Model\VideoRepository;
use Dodici\Fansworld\WebBundle\Entity\Notification;

/**
 * Video controller.
 * @Route("/video")
 */
class VideoUploadController extends SiteController
{
    const DEFAULT_VIDEO_FILE_PATH = '../DataFixtures/Files/users/fansworld.jpg';

    /**
     * @Route("/test/ks", name="video_test_ks")
     * @Route("/upload/ks", name="video_kaltura_ks")
     */
    public function kalturaKsAction()
    {
        $user = $this->getUser();
        if (!$user) throw new AccessDeniedHttpException('User not logged in');

        $kaltura = $this->get('kaltura');
        $ks = $kaltura->getKs(false, $user->getId());
        $url = $kaltura->getApiUrl();

        return $this->jsonResponse(
            array(
                'url' => $url,
                'ks' => $ks
            )
        );
    }

    /**
     *  @Route("/ajax/upload-youtube", name="video_ajaxupload_youtube")
     * Test method
     */
    public function ajaxUploadYoutube()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $youtubeLink = $request->get('link', false);
        $videouploader = $this->get('video.uploader');

        $video = new Video();
        $video = $videouploader->createVideoFromUrl($youtubeLink, $user);

        $video->setPrivacy(1);
        $videocategory = $this->getRepository('VideoCategory')->find(3);
        $video->setVideocategory($videocategory);

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($video);
        $em->flush();

        $this->get('session')->setFlash('success', '¡Has subido un video de Youtube con éxito!');
        return $this->jsonResponse(array('success' => true));
    }

    /**
     * @Route("/upload", name="video_upload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $privacies = Privacy::getOptions();

        $categories = $this->getRepository('VideoCategory')->findBy(array(), array('title' => 'ASC'));
        $choicecat = array();
        foreach ($categories as $cat)
            $choicecat[$cat->getId()] = $cat;

        $genres = $this->getRepository('Genre')->findBy(array(), array('parent' => 'ASC'));
        $choisegenre = array();
        foreach ($genres as $gen)
            $choisegenre[$gen->getId()] = $gen;


        $video = null;

        $defaultData = array();

        $collectionConstraint = new Collection(array(
                    'title' => array(new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
                    'videocategory' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\Choice(array_keys($choicecat))),
                    'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
                    'youtube' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'tagtext' => array(),
                    'taguser' => array(),
                    'genre' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\Choice(array_keys($choisegenre)))
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => false, 'label' => 'Título'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('videocategory', 'choice', array('required' => true, 'choices' => $choicecat, 'label' => 'Categoría'))
                ->add('youtube', 'text', array('required' => true, 'label' => 'URL Youtube'))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
                ->add('tagtext', 'hidden', array('required' => false))
                ->add('taguser', 'hidden', array('required' => false))
                ->add('genre', 'choice', array('required' => true, 'choices' => $choisegenre, 'label' => 'Genero'))
                ->getForm();


        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    try {
                        $videouploader = $this->get('video.uploader');
                        $idyoutube = $videouploader->getYoutubeId($data['youtube']);
                        if (!$idyoutube)
                            throw new \Exception('URL inválida');

                        $metadata = $videouploader->getYoutubeMetadata($idyoutube);
                        if (!$metadata)
                            throw new \Exception('No se encontró metadata youtube');

                        $image = null;
                        if ($metadata['thumbnail_url']) {
                            $image = $this->get('appmedia')->createImageFromUrl($metadata['thumbnail_url']);
                        }

                        $videocategory = $this->getRepository('VideoCategory')->find($data['videocategory']);
                        $genre = $this->getRepository('Genre')->find($data['genre']);

                        $video = new Video();
                        $video->setAuthor($user);
                        $video->setTitle($data['title'] ? : $metadata['title']);
                        $video->setContent($data['content']);
                        $video->setYoutube($idyoutube);
                        $video->setImage($image);
                        $video->setPrivacy($data['privacy']);
                        $video->setVideocategory($videocategory);
                        $video->setGenre($genre);
                        $em->persist($video);
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

                        $this->get('tagger')->tag($user, $video, $tagitems);

                        $this->get('session')->setFlash('success', '¡Has subido un video con éxito!');
                    } catch (\Exception $e) {
                        $form->addError(new FormError($e->getMessage()));
                        $video = null;
                    }
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo video'));
            }
        }
        return array('video' => $video, 'form' => $form->createView());
    }

    /**
     * @Route("/fileupload", name="video_fileupload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fileUploadAction()
    {
        $request = $this->getRequest();
        $defaultData = array();
        $video = null;
        $user = $this->getUser();

        $form = $this->_createVideoForm($user->getId());

        if ($request->getMethod() == 'POST') {
            try {

                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {

                    $em = $this->getDoctrine()->getEntityManager();
                    $videoCategory = $this->getRepository('VideoCategory')->find($data['categories']);
                    $genre = $this->getRepository('Genre')->find($data['genre']);

                    $video = new Video();
                    $video->setAuthor($user);
                    $video->setTitle($data['title']);
                    $video->setContent($data['content']);
                    $video->setStream($data['entryid']);
                    $video->setPrivacy($data['privacy']);
                    $video->setVideocategory($videoCategory);
                    $video->setGenre($genre);
                    $video->setImage($this->_getDefaultImage());
                    $video->setActive(false);
                    $em->persist($video);
                    $em->flush();

                    $tagtexts = explode(',', $data['tagtext']);
                    $tagidols = explode(',', $data['tagidol']);
                    $tagteams = explode(',', $data['tagteam']);
                    $tagusers = explode(',', $data['taguser']);
                    $tagitems = $this->_tagEntity($tagtexts, $tagidols, $tagteams, $tagusers, $user, $video);

                    function toBoolean(&$var) {$var = $var == 'true' ? true : false;}
                    $shareEntities = array(
                        "idols" => $data['shareidol'],
                        "teams" => $data['shareteam'],
                        "users" => $data['shareuser']
                    );
                    $this->_shareVideo($video, toBoolean($data['fb']), toBoolean($data['tw']), toBoolean($data['fw']), $data['title'], $shareEntities);

                    // $this->get('session')->setFlash('success', $this->trans('upload_sucess'));
                    // $redirectColorBox = true;

                    // Set data on kaltura
                    try {
                        $entrydata = array(
                            'name' => $video->getTitle(),
                            'description' => $video->getContent()
                        );

                        $tagsintext = array();
                        foreach ($tagitems as $ti) $tagsintext[] = (string)$ti;
                        if ($tagsintext) $entrydata['tags'] = implode(', ', $tagsintext);

                        $this->get('kaltura')->updateEntry(
                            $video->getStream(),
                            $entrydata
                        );
                    } catch (\Exception $e) {
                        // error updating entry, ignore for now
                    }

                    return $this->forward('DodiciFansworldWebBundle:VideoUpload:fileMeta',
                        array('entryid' => $data['entryid'], 'title' => $data['title'],
                                'category' => $data['categories'], 'fromuploader' => true));
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo video'));
            }
        }
        return array('form' => $form->createView());
    }

    /**
     * @Route("/kalturaid", name="video_kalturaid")
     * @Secure(roles="ROLE_ADMIN")
     * @Template
     */
    public function kalturaIdAction()
    {
        $request = $this->getRequest();
        $user = $this->getUser();

        $video = new Video();

        $privacies = Privacy::getOptions();

        $videoCategories = $this->getRepository('VideoCategory')->findAll();
        $categoriesChoices = array();

        foreach ($videoCategories as $ab)
            $categoriesChoices[$ab->getId()] = $ab->getTitle();

        $genres = $this->getRepository('Genre')->findAll();
        $genrechoises = array();

        foreach ($genres as $gen)
            $genrechoises[$gen->getId()] = $gen->getTitle();

        $defaultData = array();
        $collectionConstraint = new Collection(array(
            'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
            'categories' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($categoriesChoices))),
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
            'kalturaid' => array(),
            'genre' => array()
        ));

        if ($request->getMethod() == 'GET') {
            $videoToken = $request->get('id', false);

            $video->setAuthor($user);
            $video->setTitle($videoToken);
            $video->setStream($videoToken);
            $video->setProcessed(false);
            $video->setActive(false);

            $defaultData['kalturaid'] = $request->get('id', false);
        }

        $formVideo = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
            ->add('title', 'text', array('required' => true, 'label' => 'Título'))
            ->add('categories', 'choice', array('required' => true, 'choices' => $categoriesChoices, 'label' => 'Categoria'))
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
            ->add('kalturaid', 'hidden', array('required' => true))
            ->add('genre', 'choice', array('required' => true, 'choices' => $genrechoises, 'label' => 'Genero'))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $formVideo->bindRequest($request);
            $data = $formVideo->getData();

            if ($formVideo->isValid()) {
                $video->setAuthor($user);
                $video->setTitle($data['title']);
                $video->setContent($data['content']);

                $video->setProcessed(false);
                $video->setActive(false);

                $video->setPrivacy($data['privacy']);

                $videoCategory = $this->getRepository('VideoCategory')->find($data['categories']);
                $video->setVideocategory($videoCategory);

                $genre = $this->getRepository('Genre')->find($data['genre']);
                $video->setGenre($genre);

                $video->setStream($data['kalturaid']);

                $videoImage = $this->getImageUrl($video->getImage());

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($video);
                $em->flush();

                $tagtexts = explode(',', $data['tagtext']);
                $tagidols = explode(',', $data['tagidol']);
                $tagteams = explode(',', $data['tagteam']);
                $tagusers = explode(',', $data['taguser']);
                $tagitems = $this->_tagEntity($tagtexts, $tagidols, $tagteams, $tagusers, $user, $video);

                function toBoolean(&$var) {$var = $var == 'true' ? true : false;}
                $shareEntities = array(
                    "idols" => $data['shareidol'],
                    "teams" => $data['shareteam'],
                    "users" => $data['shareuser']
                );
                $this->_shareVideo($video, toBoolean($data['fb']), toBoolean($data['tw']), toBoolean($data['fw']), $data['title'], $shareEntities);

                return $this->forward('DodiciFansworldWebBundle:VideoUpload:fileMeta',
                    array('entryid' => $data['kalturaid'], 'title' => $data['title'],
                            'category' => $data['categories'], 'fromuploader' => true));
            }
        }

        return array('form' => $formVideo->createView());
    }

    /**
     * @Route("/youtubeupload", name="video_youtubeupload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function youtubeUploadAction()
    {
        $request = $this->getRequest();
        $defaultData = array();
        $videotemp = new Video();
        $youtubeLink = '';
        $user = $this->getUser();

        if ($request->getMethod() == 'GET') {
            $youtubeLink = $request->get('link', false);
            $videotemp = $this->get('video.uploader')->createVideoFromUrl($youtubeLink, $user);
        }

        $form = $this->_createYoutubeVideoForm($user->getId(), $videotemp, $youtubeLink);

        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $video = new Video();
                    $videouploader = $this->get('video.uploader');
                    $video = $videouploader->createVideoFromUrl($data['youtubelink'], $user);

                    $video->setPrivacy($data['privacy']);

                    $videoCategory = $this->getRepository('VideoCategory')->find($data['categories']);
                    $video->setVideocategory($videoCategory);

                    $genre = $this->getRepository('Genre')->find($data['genre']);
                    $video->setGenre($genre);

                    $videoImage = $this->getImageUrl($video->getImage());

                    $em = $this->getDoctrine()->getEntityManager();
                    $em->persist($video);
                    $em->flush();

                    $tagtexts = explode(',', $data['tagtext']);
                    $tagidols = explode(',', $data['tagidol']);
                    $tagteams = explode(',', $data['tagteam']);
                    $tagusers = explode(',', $data['taguser']);
                    $tagitems = $this->_tagEntity($tagtexts, $tagidols, $tagteams, $tagusers, $user, $video);

                    function toBoolean(&$var) {$var = $var == 'true' ? true : false;}
                    $shareEntities = array(
                        "idols" => $data['shareidol'],
                        "teams" => $data['shareteam'],
                        "users" => $data['shareuser']
                    );
                    $this->_shareVideo($video, toBoolean($data['fb']), toBoolean($data['tw']), toBoolean($data['fw']), $data['title'], $shareEntities);

                    /*
                    // Notification for Youtube Upload
                    $notification = new Notification();
                    $notification->setType(Notification::TYPE_VIDEO_PROCESSED);
                    $notification->setAuthor($video->getAuthor());
                    $notification->setTarget($video->getAuthor());
                    $notification->setVideo($video);
                    $em->persist($notification);
                    */
                    return $this->forward('DodiciFansworldWebBundle:VideoUpload:fileMetaYoutube',
                        array('title' => $data['title'], 'image' => $videoImage));
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo video'));
            }
        }
        return array('form' => $form->createView());
    }

    /**
     * Ajax upload video (kaltura / youtube )
     * @Route("/ajax/uploadvideo", name="video_ajaxuploadvideo")
     */
    public function uploadVideoAction()
    {
        $request = $this->getRequest();
        $video = null;

        $user = $this->getUser();
        $genre = $request->get('genre');
        $categories = $request->get('category');
        $content = $request->get('content');
        $title = $request->get('title');
        $entryid = $request->get('entryid');
        $youtube = $request->get('youtube');
        $defaultImage = $this->_getDefaultImage();
     
        $videoCategory = $this->getRepository('VideoCategory')->find($categories);
        $genre = $this->getRepository('Genre')->find($genre);

        if ($entryid) {
            $video = new Video();
            $video->setAuthor($user);
            $video->setTitle($title);
            $video->setContent($content);
            $video->setStream($entryid);
            $video->setImage($defaultImage);
            $video->setActive(false);
        } else {
            $video = $this->get('video.uploader')->createVideoFromUrl($youtube, $user);
        }

        $video->setPrivacy(Privacy::EVERYONE);
        $video->setVideocategory($videoCategory);
        $video->setGenre($genre);

        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($video);
        $em->flush();

        if (!($video->getId()))
            throw new \Exception('Error al crear video');

        $tagtexts = explode(',', $request->get('tagtext'));
        $tagidols = explode(',', $request->get('tagidol'));
        $tagteams = explode(',', $request->get('tagteam'));
        $tagusers = explode(',', $request->get('taguser'));
        $tagitems = $this->_tagEntity($tagtexts, $tagidols, $tagteams, $tagusers, $user, $video);

        if ($entryid) {
            // Set data on Kaltura
            try {
                $entrydata = array('name' => $video->getTitle(), 'description' => $video->getContent());
                $tagsintext = array();
                foreach ($tagitems as $ti) $tagsintext[] = (string)$ti;
                if ($tagsintext) $entrydata['tags'] = implode(', ', $tagsintext);
                $this->get('kaltura')->updateEntry($video->getStream(), $entrydata);
            } catch (\Exception $e) {
                // error updating entry, ignore for now
            }
        }
        
        return $this->jsonResponse(array('response' => true));
    }



    private function _createVideoForm ($userId) {
        $privacies = Privacy::getOptions();

        foreach ($privacies as &$newpri) {
            $newpri = $this->trans($newpri);
        }

        $videoCategories = $this->getRepository('VideoCategory')->findAll();
        $categoriesChoices = array();
        foreach ($videoCategories as $ab)
            $categoriesChoices[$ab->getId()] = $ab->getTitle();

        $genres = $this->getRepository('Genre')->findBy(array('parent' => null));
        $genrechoises = array();
        foreach ($genres as $gen) {
            $children = $gen->getChildren();
            $childarray = array();
            foreach ($children as $child) {
                $childarray[$child->getId()] = $child->getTitle();
            }

            $genrechoises[$gen->getTitle()] = $childarray;
        }

        $defaultData = array();
        $collectionConstraint = new Collection(array(
            'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
            'categories' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($categoriesChoices))),
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
            'entryid' => array(),
            'genre' => array()
        ));

        $formVideo = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
            ->add('title', 'text', array('required' => true, 'label' => 'Título'))
            ->add('categories', 'choice', array('required' => true, 'choices' => $categoriesChoices, 'label' => 'Categoria'))
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
            ->add('entryid', 'hidden', array('required' => true))
            ->add('genre', 'choice', array('required' => true, 'choices' => $genrechoises, 'label' => 'Genero'))
            ->getForm();
        return $formVideo;
    }

    private function _createYoutubeVideoForm ($userId, $videotemp, $youtubeLink) {
        $privacies = Privacy::getOptions();
        $videoCategories = $this->getRepository('VideoCategory')->findAll();
        $categoriesChoices = array();
        foreach ($videoCategories as $ab)
            $categoriesChoices[$ab->getId()] = $ab->getTitle();

        $genres = $this->getRepository('Genre')->findAll();
        $genrechoises = array();
        foreach ($genres as $gen)
            $genrechoises[$gen->getId()] = $gen->getTitle();

        $defaultData = array();
        $collectionConstraint = new Collection(array(
            'title' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
            'categories' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($categoriesChoices))),
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
            'youtubelink' => array(),
            'genre' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($genrechoises)))
        ));

        $defaultData['title'] = $videotemp->getTitle();
        $defaultData['content'] = $videotemp->getContent();
        $defaultData['youtubelink'] = $youtubeLink;

        $formVideo = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
            ->add('title', 'text', array('required' => true, 'label' => 'Título'))
            ->add('categories', 'choice', array('required' => true, 'choices' => $categoriesChoices, 'label' => 'Categoria'))
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
            ->add('youtubelink', 'hidden', array('required' => true))
            ->add('genre', 'choice', array('required' => true, 'choices' => $genrechoises, 'label' => 'Genero'))
            ->getForm();
        return $formVideo;
    }

    private function _tagEntity ($tagtexts, $tagidols, $tagteams, $tagusers, $user, $video) {
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
            $this->get('tagger')->tag($user, $video, $tagitems);

        return $tagitems;
    }

    private function _shareVideo ($video, $toFb, $toTw, $toFw, $shareMessage, $entities) {
        $idolrepo = $this->getRepository('Idol');
        $teamrepo = $this->getRepository('Team');
        $userrepo = $this->getRepository('User');
        $response = array('error' => false, 'msg' => 'Sent...');

        if ($this->getUser() instanceof User) {
            if ($toFb) {
                $facebook = $this->get('app.facebook');
                $facebook instanceof AppFacebook;
                try {
                    $facebook->entityShare($video, $shareMessage);
                } catch (\Exception $exc) {
                    $response['error'] = true;
                    $response['msg'] = $exc->getMessage();
                }
            }

            if ($toTw) {
                $twitter = $this->get('app.twitter');
                $twitter instanceof AppTwitter;
                try {
                    $twitter->entityShare($video, $shareMessage);
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
                    $sharer->share($video, $shareEntities, $shareMessage, $this->getUser());
                }
            }
        }
    }

    private function _getDefaultImage() {
        $imgpath = __DIR__ . '/' .  self::DEFAULT_VIDEO_FILE_PATH;
        $imagecontent = file_get_contents($imgpath);
        $appmedia = $this->container->get('appmedia');
        $image = $appmedia->createImageFromBinary($imagecontent);
        return $image;
    }

    /**
     * @Route("/ajax/fileupload", name="video_ajaxfileupload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function ajaxFileUploadAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $user = $this->getUser();
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $response = array('error' => 'Could not save uploaded file.' . 'The upload was cancelled, or server error encountered');

            $mediaManager = $this->get("sonata.media.manager.media");
            $video = null;

            $input = fopen("php://input", "r");
            $videocontent = stream_get_contents($input);
            fclose($input);

            if ($videocontent !== false) {
                $name = $request->query->get('qqfile');
                $pathinfo = pathinfo($name);
                $filename = $pathinfo['filename'];
                $ext = $pathinfo['extension'];
                $tmpName = $filename . '.' . $ext;

                $videoToken = $this->get('video.uploader')->createVideoFromBinary($videocontent, $user, $tmpName);


                $video = new Video();
                $video->setAuthor($user);
                $video->setTitle($tmpName);
                $video->setStream($videoToken);
                $video->setProcessed(false);
                $video->setActive(false);


                $em->persist($video);
                $em->flush();

                $response = array('success' => true, 'videoid' => $video->getId());
            }
            return $this->jsonResponse($response);
        }
        return array();
    }

    /**
     * @Route("/fileupload/{fromuploader}", name="video_filemeta", defaults = {"fromuploader" = false})
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fileMetaAction($fromuploader)
    {

        $user = $this->getUser();
        $this->get('session')->setFlash('success', '¡Has subido un video con éxito!');
        $redirectColorBox = true;


        return array('redirectColorBox' => $redirectColorBox, 'user' => $user);
    }

    /**
     * @Route("/fileuploadyoutube/{title}/{image}", name="video_filemetayoutube")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fileMetaYoutubeAction($title, $image)
    {
        $user = $this->getUser();
        $this->get('session')->setFlash('success', '¡Has subido un video de Youtube con éxito!');
        return array('title' => $title, 'image' => $image, 'user' => $user);
    }

}
