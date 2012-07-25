<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\MediaBundle\Entity\Media;
use JMS\SecurityExtraBundle\Annotation\Secure;
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

/**
 * Video controller.
 * @Route("/video")
 */
class VideoUploadController extends SiteController
{

    /**
     * @Route("/upload", name="video_upload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $privacies = Privacy::getOptions();

        $categories = $this->getRepository('VideoCategory')->findBy(array(), array('title' => 'ASC'));
        $choicecat = array();
        foreach ($categories as $cat)
            $choicecat[$cat->getId()] = $cat;

        $video = null;

        $defaultData = array();

        $collectionConstraint = new Collection(array(
                    'title' => array(new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
                    'videocategory' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\Choice(array_keys($choicecat))),
                    'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
                    'youtube' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'tagtext' => array(),
                    'taguser' => array()
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => false, 'label' => 'Título'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('videocategory', 'choice', array('required' => true, 'choices' => $choicecat, 'label' => 'Categoría'))
                ->add('youtube', 'text', array('required' => true, 'label' => 'URL Youtube'))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
                ->add('tagtext', 'hidden', array('required' => false))
                ->add('taguser', 'hidden', array('required' => false))
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

                        $video = new Video();
                        $video->setAuthor($user);
                        $video->setTitle($data['title'] ? : $metadata['title']);
                        $video->setContent($data['content']);
                        $video->setYoutube($idyoutube);
                        $video->setImage($image);
                        $video->setPrivacy($data['privacy']);
                        $video->setVideocategory($videocategory);
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
        $user = $this->get('security.context')->getToken()->getUser();

        $collectionConstraint = new Collection(array(
                    'videourl' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('videourl', 'text', array('required' => true, 'label' => 'URL Video'))
                ->getForm();

        if ($request->getMethod() == 'POST') {

            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    try {
                        $videotemp = $this->get('video.uploader')->createVideoFromUrl($data['videourl'], $user);
                        $em = $this->getDoctrine()->getEntityManager();
                        $em->persist($videotemp);
                        $em->flush();

                        return $this->forward('DodiciFansworldWebBundle:VideoUpload:fileMeta', array(
                                    'videotemp' => $videotemp->getId(),
                                    'fromuploader' => true
                                ));
                    } catch (\Exception $e) {
                        $form->addError(new FormError($e->getMessage()));
                        $video = null;
                    }
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo video'));
            }
        }


        return array('form' => $form->createView());
    }

    /**
     * @Route("/ajax/fileupload", name="video_ajaxfileupload")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function ajaxFileUploadAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $user = $this->get('security.context')->getToken()->getUser();
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
     * @Route("/fileupload/{videotemp}/{fromuploader}", name="video_filemeta", defaults = {"fromuploader" = false})
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fileMetaAction($videotemp, $fromuploader)
    {
        $em = $this->getDoctrine()->getEntityManager();


        $video = $this->getRepository('Video')->findOneBy(array('id' => $videotemp));
        $thumbnail = null;

        $redirectColorBox = false;
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();

        $privacies = Privacy::getOptions();

        $categories = $this->getRepository('VideoCategory')->findBy(array(), array('title' => 'ASC'));
        $choicecat = array();
        foreach ($categories as $cat)
            $choicecat[$cat->getId()] = $cat;
        $videouploader = $this->get('video.uploader');


        $defaultData = array();

        $defaultData = array(
            'title' => $video->getTitle(),
        );


        $collectionConstraint = new Collection(array(
                    'title' => array(new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
                    'videocategory' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\Choice(array_keys($choicecat))),
                    'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
                    'tagtext' => array(),
                    'taguser' => array()
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => false, 'label' => 'Título'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('videocategory', 'choice', array('required' => true, 'choices' => $choicecat, 'label' => 'Categoría'))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
                ->add('tagtext', 'hidden', array('required' => false))
                ->add('taguser', 'hidden', array('required' => false))
                ->getForm();

        if ($fromuploader == false) {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    try {


                        $image = null;
                        if (!$video->getImage()) {
                            /*
                              $image = $this->get('appmedia')->createImageFromUrl($video->getImage());
                              $video->setImage($image); */
                        }


                        $videocategory = $this->getRepository('VideoCategory')->find($data['videocategory']);


                        $video->setAuthor($user);
                        $video->setTitle($data['title'] ? : $defaultData['title']);
                        $video->setContent($data['content']);

                        $video->setPrivacy($data['privacy']);
                        $video->setVideocategory($videocategory);
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
                        $redirectColorBox = true;
                    } catch (\Exception $e) {
                        $form->addError(new FormError($e->getMessage()));
                        $video = null;
                    }
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo video'));
            }
        }


        return array('video' => $video, 'form' => $form->createView(), 'redirectColorBox' => $redirectColorBox, 'videotemp' => $videotemp, 'thumbnail_url' => $thumbnail, 'user' => $user);
    }

}
