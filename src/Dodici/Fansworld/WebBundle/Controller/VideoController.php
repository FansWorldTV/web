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

/**
 * Video controller.
 * @Route("/video")
 */
class VideoController extends SiteController
{

    const cantVideos = 10;

    /**
     * @Route("/{id}/{slug}", name= "video_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template
     */
    public function showAction($id)
    {
        $video = $this->getRepository('Video')->findOneBy(array('id' => $id, 'active' => true));

        $this->securityCheck($video);

        return array('video' => $video);
    }

    /**
     * video list
     * 
     * @Route("/list", name="video_list")
     * @Template
     */
    public function listAction()
    {
        $videos = $this->getRepository("Video")->findBy(array("active" => true), array("createdAt" => "DESC"), self::cantVideos);
        $countAll = $this->getRepository('Video')->countBy(array('active' => true));
        $addMore = $countAll > self::cantVideos ? true : false;
        $categories = $this->getRepository('VideoCategory')->findBy(array());
        $popularTags = $this->getRepository('Tag')->findBy(array(), array('useCount' => 'DESC'), 10);

        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'categories' => $categories,
            'popularTags' => $popularTags
        );
    }

    /**
     * @Route("/ajax/search", name="video_ajaxsearch") 
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();

        $page = $request->get('page');
        $query = $request->get('query', false);
        $categoryId = $request->get('category', null);

        $user = $this->get('security.context')->getToken()->getUser();

        $page = (int) $page;
        $offset = ($page - 1) * self::cantVideos;

        $response = array();

        if (!$query) {
            $videos = $this->getRepository('Video')->findBy(array('active' => true, 'videocategory' => $categoryId), array('createdAt' => 'DESC'), self::cantVideos, $offset);
            $countAll = $this->getRepository('Video')->countBy(array('active' => true, 'videocategory' => $categoryId));
        } else {
            $videos = $this->getRepository('Video')->searchText($query, $user, self::cantVideos, $offset, $categoryId);
            $countAll = $this->getRepository('Video')->countSearchText($query, $user, $categoryId);
        }

        $response['addMore'] = $countAll > (($page) * self::cantVideos) ? true : false;
        foreach ($videos as $video) {
            $tags = array();
            foreach ($video->getHastags() as $tag) {
                array_push($tags, array(
                    'title' => $tag->getTag()->getTitle(),
                    'slug' => $tag->getTag()->getSlug()
                        )
                );
            }

            $response['videos'][] = array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'image' => $this->getImageUrl($video->getImage()),
                'author' => array(
                    'name' => (string) $video->getAuthor(),
                    'id' => $video->getAuthor()->getId()
                ),
                'date' => $video->getCreatedAt()->format('c'),
                'content' => substr($video->getContent(), 0, 100),
                'slug' => $video->getSlug(),
                'tags' => $tags
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * video category page
     * 
     * @Route("/category/{id}/{slug}", name="video_category", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template
     */
    public function categoryAction($id)
    {
        $category = $this->getRepository('VideoCategory')->find($id);
        if (!$category)
            throw new HttpException(404, 'Categoría no encontrada');
        $videos = $this->getRepository("Video")->findBy(array("active" => true, "videocategory" => $category->getId()), array("createdAt" => "DESC"), self::cantVideos);
        $countAll = $this->getRepository('Video')->countBy(array('active' => true, "videocategory" => $category->getId()));

        $addMore = $countAll > self::cantVideos ? true : false;

        $categories = $this->getRepository('VideoCategory')->findBy(array());
        $popularTags = $this->getRepository('Tag')->findBy(array(), array('useCount' => 'DESC'), 10);

        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'categories' => $categories,
            'popularTags' => $popularTags,
            'selected' => $id
        );
    }

    /**
     * my videos
     * 
     * @Route("/my-videos/{userid}", name="video_myvideos") 
     * @Template
     */
    public function myVideosAction($userid)
    {
        $user = $this->getRepository('User')->find($userid);
        $videos = $this->getRepository('Video')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'desc'), self::cantVideos);
        $countAll = $this->getRepository('Video')->countBy(array('author' => $user->getId()));
        $addMore = $countAll > self::cantVideos ? true : false;

        return array(
            'videos' => $videos,
            'addMore' => $addMore
        );
    }
    
    /**
     * @Route("/ajax/myVideos", name="video_ajaxmyvideos") 
     */
    public function ajaxMyVideos()
    {
        $request = $this->getRequest();

        $page = $request->get('page');
        $userid = $request->get('userid', false);

        $user = $this->getRepository('User')->find($userid);

        $page = (int) $page;
        $offset = ($page - 1) * self::cantVideos;

        $response = array();

        $videos = $this->getRepository('Video')->findBy(array('active' => true, 'author' => $userid), array('createdAt' => 'DESC'), self::cantVideos, $offset);
        $countAll = $this->getRepository('Video')->countBy(array('active' => true, 'author' => $userid));

        $response['addMore'] = $countAll > (($page) * self::cantVideos) ? true : false;
        foreach ($videos as $video) {
            $tags = array();
            foreach ($video->getHastags() as $tag) {
                array_push($tags, array(
                    'title' => $tag->getTag()->getTitle(),
                    'slug' => $tag->getTag()->getSlug()
                        )
                );
            }

            $response['videos'][] = array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'image' => $this->getImageUrl($video->getImage()),
                'author' => array(
                    'name' => (string) $video->getAuthor(),
                    'id' => $video->getAuthor()->getId()
                ),
                'date' => $video->getCreatedAt()->format('c'),
                'content' => substr($video->getContent(), 0, 100),
                'slug' => $video->getSlug(),
                'tags' => $tags
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * Show by tag
     * 
     * @Route("/tag/{slug}", name = "video_tags")
     * @Template()
     */
    public function tagsAction($slug)
    {
        $categories = $this->getRepository('VideoCategory')->findBy(array());
        $popularTags = $this->getRepository('Tag')->findBy(array(), array('useCount' => 'DESC'), 10);

        $videos = array();
        if ($slug) {
            $user = $this->get('security.context')->getToken()->getUser();
            $tag = $this->getRepository('Tag')->findOneBy(array('title' => $slug));

            $videosRepo = $this->getRepository('Video')->byTag($tag, $user, self::cantVideos);
            foreach ($videosRepo as $video) {
                $videos[] = $video;
            }

            $id = $tag->getId();
        }

        $countAll = $this->getRepository('Video')->countByTag($tag, $user);
        $addMore = $countAll > self::cantVideos ? true : false;


        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'categories' => $categories,
            'popularTags' => $popularTags,
            'selected' => $id,
            'slug' => $slug
        );
    }

    /**
     * search videos by tag
     * 
     * @Route("/ajax/search-by-tag", name="video_ajaxsearchbytag") 
     */
    public function ajaxSearchByTagAction()
    {
        $request = $this->getRequest();
        $page = (int) $request->get('page', 1);
        $id = $request->get('id', false);
        $offset = ($page - 1) * self::cantVideos;
        $user = $this->get('security.context')->getToken()->getUser();
        $tag = $this->getRepository('Tag')->find($id);

        $videos = array();
        if ($id) {
            $videosRepo = $this->getRepository('Video')->byTag($tag, $user, self::cantVideos, $offset);
            foreach ($videosRepo as $video) {
                $tags = array();
                foreach ($video->getHastags() as $tag) {
                    array_push($tags, array(
                        'title' => $tag->getTag()->getTitle(),
                        'slug' => $tag->getTag()->getSlug()
                            )
                    );
                }

                $videos[] = array(
                    'id' => $video->getId(),
                    'title' => $video->getTitle(),
                    'image' => $this->getImageUrl($video->getImage()),
                    'author' => array(
                        'name' => (string) $video->getAuthor(),
                        'id' => $video->getAuthor()->getId()
                    ),
                    'date' => $video->getCreatedAt()->format('c'),
                    'content' => substr($video->getContent(), 0, 100),
                    'slug' => $video->getSlug(),
                    'tags' => $tags
                );
            }
        }

        $tag = $this->getRepository('Tag')->find($id);
        $countAll = $this->getRepository('Video')->countByTag($tag, $user);
        $addMore = $countAll > (($page) * self::cantVideos) ? true : false;


        return $this->jsonResponse(array(
            'videos' => $videos,
            'addMore' => $addMore
        ));
    }

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

        $video = null;

        $defaultData = array();

        $collectionConstraint = new Collection(array(
                    'title' => array(new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                    'content' => new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 400)),
                    'privacy' => array(new \Symfony\Component\Validator\Constraints\Choice(array_keys($privacies))),
                    'youtube' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250)))
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('title', 'text', array('required' => false, 'label' => 'Título'))
                ->add('content', 'textarea', array('required' => false, 'label' => 'Descripción'))
                ->add('youtube', 'text', array('required' => true, 'label' => 'URL Youtube'))
                ->add('privacy', 'choice', array('required' => true, 'choices' => $privacies, 'label' => 'Privacidad'))
                ->getForm();


        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    try {
                        $flutwig = $this->get('flumotiontwig');
                        $idyoutube = $flutwig->getYoutubeId($data['youtube']);
                        if (!$idyoutube)
                            throw new \Exception('URL inválida');

                        $metadata = $flutwig->getYoutubeMetadata($idyoutube);
                        if (!$metadata)
                            throw new \Exception('No se encontró metadata youtube');

                        $image = null;
                        $imagecontent = @file_get_contents($metadata['thumbnail_url']);
                        if ($imagecontent) {
                            $tmpfile = tempnam('/tmp', 'IYT');
                            file_put_contents($tmpfile, $imagecontent);
                            $mediaManager = $this->get("sonata.media.manager.media");
                            $image = new Media();
                            $image->setBinaryContent($tmpfile);
                            $image->setContext('default');
                            $image->setProviderName('sonata.media.provider.image');
                            $mediaManager->save($image);
                        }

                        $video = new Video();
                        $video->setAuthor($user);
                        $video->setTitle($data['title'] ? : $metadata['title']);
                        $video->setContent($data['content']);
                        $video->setYoutube($idyoutube);
                        $video->setImage($image);
                        $video->setPrivacy($data['privacy']);
                        $em->persist($video);
                        $em->flush();
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

}
