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
use Dodici\Fansworld\WebBundle\Entity\Visit;
use Dodici\Fansworld\WebBundle\Model\VisitRepository;
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

        $this->get('visitator')->visit($video);
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
        $mostviewed = $vidrepo->search(null, $user, 3, null, null, null, null, null, null, 'views');
        $mostliked = $vidrepo->search(null, $user, 3, null, null, null, null, null, null, 'likes');

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
            $catvids = $vidrepo->search(null, $user, 2, null, $category, true);
            $videosbycat[] = array('category' => $category, 'videos' => $catvids);
        }

        $uservideos = $vidrepo->search($query, $user, 12, null, null, false);



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
            $videos = $this->getRepository('Video')->search($query, $user, self::cantVideos, $offset, $categoryId);
            $countAll = $this->getRepository('Video')->countSearch($query, $user, $categoryId);
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

        $categoryVids = $vidRepo->search(null, $user, 12, null, $category);
        $countAll = $vidRepo->countSearch(null, $user, $category);
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

        $categoryVids = $vidRepo->search($query, $user, 12, $offset, $categoryId);
        $countAll = $vidRepo->countSearch($query, $user, $categoryId);


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

        $videos = $videosRepo->search($query, $user, 16, null, null, false);
        $countAll = $videosRepo->countSearch($query, $user, null, false);

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

        $videos = $videosRepo->search($query, $user, 16, $offset, null, false);
        $countAll = $videosRepo->countSearch($query, $user, null, false);

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

        if (!$user) {
            throw new HttpException(404, "No existe el usuario");
        }else
            $this->get('visitator')->visit($user);

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
     * @Route("/ajax/highlighted-videos", name="video_highlighted")
     */
    public function ajaxHighlightVideosAction()
    {
        $request = $this->getRequest();

        $response = array(
            'videos' => array()
        );

        $user = $request->get('userId', false);
        $page = $request->get('page', 1);

        $repoVideos = $this->getRepository('Video');
        $repoVideos instanceof VideoRepository;

        $page = (int) $page;
        $offset = ($page - 1) * self::cantVideos;

        $videos = $repoVideos->findBy(array('author' => $user, 'highlight' => true), array('createdAt' => 'desc'), self::cantVideos, $offset);

        foreach ($videos as $video) {
            $response['videos'][] = array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'slug' => $video->getSlug(),
                'image' => $this->getImageUrl($video->getImage, 'medium')
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/ajax/visited-videos", name="video_visitedvideos")
     */
    public function ajaxVisitVideosAction()
    {
        $request = $this->getRequest();

        $user = $request->get('userId', false);
        if (!$user) {
            $user = $this->get('security.context')->getToken()->getUser()->getId();
        }
        
        $today = $request->get('today', false);
        $popular = $request->get('isPopular', false);
        
        $page = $request->get('page', 1);
        $offset = ($page - 1) * self::cantVideos;

        $response = array(
            'visits' => array()
        );

        $videoRepo = $this->getRepository('Video');
        $videoRepo instanceof VideoRepository;

        if($today){
            $date = new \DateTime();
            $videos = $videoRepo->dateFromVideos($date, self::cantVideos, $offset);
        }else{
            $videos = $videoRepo->findBy(array('author' => $user), array('visitCount' => 'desc'), self::cantVideos, $offset);
        }

        foreach ($videos as $video) {
            $response['videos'][] = array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'slug' => $video->getSlug(),
                'image' => $this->getImageUrl($video->getImage(), 'medium'),
                'visitCount' => $video->getVisitCount()
            );
        }

        return $this->jsonResponse($response);
    }
    
    public function popularVideosAction(){
        $request = $this->getRequest();
        $user = $request->get('userid', false);
        if(!$user) {
            $user = $this->get('security.context')->getToken()->getUser()->getId();
        }
        
        $page = $request->get('page', 1);
        $offset = ($page - 1 ) * self::cantVideos;
        
        $response = array();
        
        $videos = $this->getRepository('Video')->findBy(array('author' => $user), array('weight' => 'desc'), self::cantVideos, $offset);
        
        foreach($videos as $video){
            $response['videos'][] = array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'slug' => $video->getSlug(),
                'image' => $this->getImageUrl($video->getImage(), 'medium'),
                'visitCount' => $video->getVisitCount()
            );
        }
        
        return $this->jsonResponse($response);
    }

}
