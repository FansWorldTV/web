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
        
        $this->get('visitator')->addVisit($video);
        return array('video' => $video);
    }

    /**
     * rightbar
     * @Template
     */
    public function rightbarAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $vidrepo = $this->getRepository("Video");
        $mostviewed = $vidrepo->searchText(null, $user, 3, null, null, null, 'views');
        $mostliked = $vidrepo->searchText(null, $user, 3, null, null, null, 'likes');

        return array(
            'mostviewed' => $mostviewed,
            'mostliked' => $mostliked
        );
    }

    /**
     * video list
     * 
     * @Route("/list", name="video_list")
     * @Template
     */
    public function listAction()
    {
        /* $videos = $this->getRepository("Video")->findBy(array("active" => true), array("createdAt" => "DESC"), self::cantVideos);
          $countAll = $this->getRepository('Video')->countBy(array('active' => true));
          $addMore = $countAll > self::cantVideos ? true : false;
          $categories = $this->getRepository('VideoCategory')->findBy(array());
          $popularTags = $this->getRepository('Tag')->findBy(array(), array('useCount' => 'DESC'), 10);

          return array(
          'videos' => $videos,
          'addMore' => $addMore,
          'categories' => $categories,
          'popularTags' => $popularTags
          ); */

        $request = $this->getRequest();
        $query = $request->get('query', null);
        $user = $this->get('security.context')->getToken()->getUser();
        $vidrepo = $this->getRepository("Video");
        $videosbycat = array();
        $highlight = null;
        
        $highlights = $vidrepo->findBy(array("active" => true, "highlight" => true), array("createdAt" => "DESC"), 1);
        if (count($highlights))
            $highlight = $highlights[0];
        
        $categories = $this->getRepository('VideoCategory')->findBy(array(), array('title' => 'ASC'));

        foreach ($categories as $category) {
            $catvids = $vidrepo->searchText(null, $user, 2, null, $category, false);
            $videosbycat[] = array('category' => $category, 'videos' => $catvids);
        }

        $uservideos = $vidrepo->searchText($query, $user, 12, null, null, true);



        return array(
            'highlight' => $highlight,
            'videosbycategory' => $videosbycat,
            'uservideos' => $uservideos
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
                    'id' => $video->getAuthor()->getId(),
                    'avatar' => $this->getImageUrl($video->getAuthor()->getImage())
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

        $user = $this->get("security.context")->getToken()->getUser();

        $vidRepo = $this->getRepository('Video');

        $categories = $this->getRepository('VideoCategory')->findBy(array());
        $popularTags = $this->getRepository('Tag')->findBy(array(), array('useCount' => 'DESC'), 10);


        $highlight = null;
        $highlights = $vidRepo->findBy(array("active" => true, "highlight" => true, "videocategory" => $category->getId()), array("createdAt" => "DESC"), 1);
        if (count($highlights))
            $highlight = $highlights[0];

        $categoryVids = $vidRepo->searchText(null, $user, 12, null, $category);
        $countAll = $vidRepo->countSearchText(null, $user, $category);
        $addMore = $countAll > 12 ? true : false;

        return array(
            'addMore' => $addMore,
            'categories' => $categories,
            'popularTags' => $popularTags,
            'selected' => $id,
            'categoryVids' => $categoryVids,
            'highlight' => $highlight
        );
    }

    /**
     * get category videos
     *  @Route("/ajax/category", name="video_ajaxcategory") 
     */
    public function ajaxCategoryVidsAction()
    {
        $request = $this->getRequest();
        $categoryId = $request->get('id');
        $page = (int) $request->get('page');
        $query = $request->get('query', null);
        
        $query = $query == "null" ? null : $query;

        $user = $this->get('security.context')->getToken()->getUser();

        if ($page > 1) {
            $offset = ($page - 1) * 12;
        } else {
            $offset = 0;
        }
        $vidRepo = $this->getRepository('Video');
        $response = array('gotMore' => false, 'vids' => null);

        $categoryVids = $vidRepo->searchText($query, $user, 12, $offset, $categoryId);
        $countAll = $vidRepo->countSearchText($query, $user, $categoryId);


        if (($countAll / 12) > $page) {
            $response['gotMore'] = true;
        }

        foreach ($categoryVids as $vid) {
            $response['vids'][] = array(
                'view' => $this->renderView('DodiciFansworldWebBundle:Video:list_video_item.html.twig', array('video' => $vid))
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * user videos page
     * 
     * @Route("/users", name="video_users")
     * @Template
     */
    public function userVideosAction()
    {
        $request = $this->getRequest();
        
        $query = $request->get('query', null);
        $query = $query == "null" ? null : $query;
        
        $videosRepo = $this->getRepository('Video');
        $videosRepo instanceof VideoRepository;

        $user = $this->get('security.context')->getToken()->getUser();

        $videos = $videosRepo->searchText($query, $user, 16, null, null, true);
        $countAll = $videosRepo->countSearchText($query, $user, null, true);

        $firstToBeHighlighted = false;
        $usersVideos = array();

        $firstElement = true;
        foreach ($videos as $video) {
            if ($firstElement) {
                $firstElement = false;
                $firstToBeHighlighted = $video;
            } else {
                array_push($usersVideos, $video);
            }
        }

        return array(
            'usersVideos' => $usersVideos,
            'highlight' => $firstToBeHighlighted,
            'addMore' => $countAll > 16 ? true : false
        );
    }

    /**
     * user videos page ajax
     * @Route("/ajax/users", name="video_ajaxusers")
     */
    public function ajaxUserVideosAction()
    {
        $request = $this->getRequest();
        $page = (int) $request->get('page', 0);
        $query = $request->get('query', null);

        $offset = $page > 0 ? ($page - 1 ) * 16 : 0;


        $videosRepo = $this->getRepository('Video');
        $videosRepo instanceof VideoRepository;

        $user = $this->get('security.context')->getToken()->getUser();

        $videos = $videosRepo->searchText($query, $user, 16, $offset, null, true);
        $countAll = $videosRepo->countSearchText($query, $user, null, true);

        $usersVideos = false;

        foreach ($videos as $video) {
            array_push($usersVideos, $this->renderView('DodiciFansworldWebBundle:Video:list_video_item.html.twig', array('video' => $video)));
        }

        $addMore = (( $countAll / 16 ) > $page) ? true : false;

        return $this->jsonResponse(array(
                    'addMore' => $addMore,
                    'videos' => $usersVideos
                ));
    }

    /**
     * my videos
     * 
     * @Route("/u/{username}", name="video_user") 
     * @Template
     */
    public function myVideosAction($username)
    {
        $videoRepo = $this->getRepository('Video');
        $user = $this->getRepository('User')->findOneByUsername($username);
        if (!$user)
            throw new HttpException(404, 'Usuario no encontrado');
        
        $videos = $videoRepo->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'desc'), self::cantVideos);
        $countAll = $videoRepo->countBy(array('author' => $user->getId()));
        $addMore = $countAll > self::cantVideos ? true : false;
        $highlightVideos = $videoRepo->findBy(array('active' => true, 'highlight' => true, 'author' => $user->getId()), array('createdAt' => 'desc'));
        $highlightVideo = count($highlightVideos) > 0 ? $highlightVideos[0] : false;
        
        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'user' => $user,
            'highlightVideo' => $highlightVideo,
            'highlightVideos' => $highlightVideos
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
                    'id' => $video->getAuthor()->getId(),
                    'avatar' => $this->getImageUrl($video->getAuthor()->getImage())
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
            $tag = $this->getRepository('Tag')->findOneBy(array('slug' => $slug));

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
                        'id' => $video->getAuthor()->getId(),
                        'avatar' => $this->getImageUrl($video->getAuthor()->getImage())
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
	                    	if (trim($tt)) $tagitems[] = $tt;
	                    }
	                    foreach ($tagusers as $tu) {
	                    	$tuser = $userrepo->find($tu);
	                    	if ($tuser) $tagitems[] = $tuser;
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
        
        $collectionConstraint = new Collection(array(
                    'youtube' => array(new NotBlank(), new \Symfony\Component\Validator\Constraints\MaxLength(array('limit' => 250))),
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('youtube', 'text', array('required' => true, 'label' => 'URL Youtube'))
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
                        
                        return $this->forward('DodiciFansworldWebBundle:Video:filemeta', array(
                        	'idyoutube' => $idyoutube,
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
        

        return array('video' => $video, 'form' => $form->createView());
    }
    
	/**
     * @Route("/fileupload/{idyoutube}/{fromuploader}", name="video_filemeta", defaults = {"fromuploader" = false})
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fileMetaAction($idyoutube,$fromuploader)
    {
        $redirectColorBox = false;
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $privacies = Privacy::getOptions();

        $categories = $this->getRepository('VideoCategory')->findBy(array(), array('title' => 'ASC'));
        $choicecat = array();
        foreach ($categories as $cat)
            $choicecat[$cat->getId()] = $cat;

        $video = null;
        
        $flutwig = $this->get('flumotiontwig');
        $metadata = $flutwig->getYoutubeMetadata($idyoutube);
        

        $defaultData = array(
            'title' => $metadata['title']
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

        if ($fromuploader !== true) {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    try {
                        if (!$idyoutube)
                            throw new \Exception('URL inválida');
                        
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
	                    	if (trim($tt)) $tagitems[] = $tt;
	                    }
	                    foreach ($tagusers as $tu) {
	                    	$tuser = $userrepo->find($tu);
	                    	if ($tuser) $tagitems[] = $tuser;
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
        

        return array('video' => $video, 'form' => $form->createView(), 'redirectColorBox' => $redirectColorBox, 'idyoutube' => $idyoutube, 'metadata' => $metadata);
    }

}
