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
 */
class VideoController extends SiteController
{

    const cantVideos = 20;
    const MIN_ITEMS_CALLTOACTION = 9;

    /**
     * rightbar
     * @Template
     */
    public function rightbarAction()
    {
        $user = $this->getUser();
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
     * @Route("/video/list", name="video_list")
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
        $user = $this->getUser();
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
     * @Route("/video/ajax/search", name="video_ajaxsearch")
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();

        $page = $request->get('page');
        $query = $request->get('query', false);
        $categorySlug = $request->get('category', null);
        if($categorySlug != null && $categorySlug != 'all'){
            $category = $this->getRepository('VideoCategory')->findBy(array('slug' => $categorySlug));
        }else $category = null;
        $filter = $request->get('filter', false);
        $user = $this->getUser();

        $page = (int) $page;
        $offset = ($page - 1) * self::cantVideos;

        $response = array('category' => $category);
        $repoVideos = $this->getRepository('Video');

        if (!$query) {
            if($filter){
                switch($filter){
                    case 'week':
                        $datefrom = new \DateTime("-1 week");
                        break;
                    case 'day':
                        $datefrom = new \DateTime("-1 day");
                        break;
                    default:
                        $datefrom = null;
                        break;
                }
            }else $datefrom = null;

            $videos = $repoVideos->search(null, null, self::cantVideos, $offset, $category, null, null, $datefrom,null,null, null);
            $countAll = $repoVideos->countSearch(null, null, $category, true, null, $datefrom, null, null);


            //$videos = $this->getRepository('Video')->findBy(array('active' => true, 'videocategory' => $categoryId), array('createdAt' => 'DESC'), self::cantVideos, $offset);
            //$countAll = $this->getRepository('Video')->countBy(array('active' => true, 'videocategory' => $categoryId));
        } else {

            $videos = $this->getRepository('Video')->search($query, $user, self::cantVideos, $offset, $category);
            $countAll = $this->getRepository('Video')->countSearch($query, $user, $category);

        }

        $response['addMore'] = $countAll > ($page * self::cantVideos) ? true : false;
        foreach ($videos as $video) {
            $tags = array();
            foreach ($video->getHastags() as $tag) {
                array_push($tags, array(
                    'title' => $tag->getTag()->getTitle(),
                    'slug' => $tag->getTag()->getSlug()
                        )
                );
            }
/*
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
            */


                $response['videos'][] = array(
                        'id' => $video->getId(),
                        'title' => $video->getTitle(),
                        'slug' => $video->getSlug(),
                        'imgsrc' => $this->getImageUrl($video->getImage(), 'medium'),
                        'visitCount' => $video->getVisitCount(),
                        'url' => $this->generateUrl('video_show', array(
                                'id' => $video->getId(),
                                'slug' => $video->getSlug()
                        ))
                );


        }

        return $this->jsonResponse($response);
    }

    /**
     * video category page
     *
     * @Route("/video/category/{id}/{slug}", name="video_category", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
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
     *  @Route("/video/ajax/category", name="video_ajaxcategory")
     */
    public function ajaxCategoryVidsAction()
    {
        $request = $this->getRequest();
        $categoryId = $request->get('id');
        $page = (int) $request->get('page');
        $query = $request->get('query', null);

        $query = $query == "null" ? null : $query;

        $user = $this->getUser();

        if ($page > 1) {
            $offset = ($page - 1) * 12;
        } else {
            $offset = 0;
        }
        $vidRepo = $this->getRepository('Video');
        $response = array('addMore' => false, 'elements' => null);

        $categoryVids = $vidRepo->search($query, $user, 12, $offset, $categoryId);
        $countAll = $vidRepo->countSearch($query, $user, $categoryId);


        if (($countAll / 12) > $page) {
            $response['addMore'] = true;
        }

        foreach ($categoryVids as $vid) {
            $response['elements'][] = array(
                'view' => $this->renderView('DodiciFansworldWebBundle:Video:list_video_item_raw.html.twig', array('video' => $vid))
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * Show by tag
     *
     * @Route("/video/tag/{slug}", name = "video_tags")
     * @Template()
     */
    public function tagsAction($slug)
    {
        $categories = $this->getRepository('VideoCategory')->findBy(array());
        $popularTags = $this->getRepository('Tag')->findBy(array(), array('useCount' => 'DESC'), 10);

        $videos = array();
        if ($slug) {
            $user = $this->getUser();
            $tag = $this->getRepository('Tag')->findOneBy(array('slug' => $slug));

            $videosRepo = $this->getRepository('Video')->search($tag, $user, self::cantVideos);
            foreach ($videosRepo as $video) {
                $videos[] = $video;
            }

            $id = $tag->getId();
        }

        $countAll = $this->getRepository('Video')->countSearch($tag, $user);
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
     * @Route("/video/ajax/search-by-tag", name="video_ajaxsearchbytag")
     */
    public function ajaxSearchByTagAction()
    {
        $request = $this->getRequest();
        $page = (int) $request->get('page', 1);
        $id = $request->get('id', false);
        $offset = ($page - 1) * self::cantVideos;
        $user = $this->getUser();

        $tag = null;
        $entity = $request->get('entity', false);

        if ($id) {

            $videos = array();
            if ('team' == $entity) $repo = "Team";
            if ('idol' == $entity) $repo = "Idol";

            if ('tag' == $entity) {
                $tag = $this->getRepository('Tag')->find($id);
                $videosRepo = $this->getRepository('Video')->search($tag, $user, self::cantVideos, $offset);
                $countAll = $this->getRepository('Video')->countSearch($tag, $user);
            } else {
                $info_entity = $this->getRepository($repo)->findOneBy(array('id' => $id));
                $videosRepo = $this->getRepository('Video')->search(null, $user, self::cantVideos, $offset, null, null, null, null, null, 'default', $info_entity);
                $countAll = $this->getRepository('Video')->countSearch(null, $user, null, null, null, null, null, $info_entity);
            }
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
                    'image' => $this->getImageUrl($video->getImage(), 'medium'),
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
        $addMore = $countAll > (($page) * self::cantVideos) ? true : false;


        return $this->jsonResponse(array(
                    'videos' => $videos,
                    'addMore' => $addMore
                ));
    }

    /**
     * @Route("/video/ajax/highlighted-videos", name="video_highlighted")
     */
    public function ajaxHighlightVideosAction()
    {
        $request = $this->getRequest();

        $response = array(
            'elements' => array()
        );

        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');

        $user = $this->getUser();

        $page = $request->get('page', 1);

        $repoVideos = $this->getRepository('Video');
        $repoVideos instanceof VideoRepository;

        $page = (int) $page;
        $offset = ($page - 1) * self::cantVideos;

        $taggedEntity = null;
        if ($entityType == 'user') {
            $author = $this->getRepository('User')->find($entityId);
        } else {
            $author = null;

            switch ($entityType) {
                case 'team':
                    $taggedEntity = $this->getRepository('Team')->find($entityId);
                    break;
                case 'idol':
                    $taggedEntity = $this->getRepository('Idol')->find($entityId);
                    break;
            }
        }

        $videos = $repoVideos->search(null, $user, self::cantVideos, $offset, null, true, $author, null, null, 'default', $taggedEntity);
        $countAll = $repoVideos->countSearch(null, $user, null, true, $author, null, null, $taggedEntity);

        $response['addMore'] = $countAll > self::cantVideos ? true : false;

        foreach ($videos as $video) {
            $response['elements'][] = $this->get('serializer')->values($video);
            /*
            array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'slug' => $video->getSlug(),
                'imgsrc' => $this->getImageUrl($video->getImage(), 'medium'),
                'visitCount' => $video->getVisitCount(),
                'url' => $this->generateUrl('video_show', array(
                    'id' => $video->getId(),
                    'slug' => $video->getSlug()
                ))
            );
            */
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/video/ajax/visited-videos", name="video_visited")
     */
    public function ajaxVisitVideosAction()
    {
        $request = $this->getRequest();

        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');

        $user = $this->getUser();

        $today = $request->get('today', false);

        $page = $request->get('page', 1);
        $offset = ($page - 1) * self::cantVideos;

        $response = array(
            'visits' => array()
        );

        $taggedEntity = null;
        if ($entityType == 'user') {
            $author = $this->getRepository('User')->find($entityId);
        } else {
            $author = null;

            switch ($entityType) {
                case 'team':
                    $taggedEntity = $this->getRepository('Team')->find($entityId);
                    break;
                case 'idol':
                    $taggedEntity = $this->getRepository('Idol')->find($entityId);
                    break;
            }
        }

        $videoRepo = $this->getRepository('Video');
        $videoRepo instanceof VideoRepository;

        if ($today) {
            $date = new \DateTime();
            $datefrom = $date->format("Y-m-d 00:00:00");
            $dateto = $date->format("Y-m-d 23:59:59");

            $videos = $videoRepo->search(null, $user, self::cantVideos, $offset, null, null, $author, $datefrom, $dateto, 'default', $taggedEntity);
            $countAll = $videoRepo->countSearch(null, $user, null, null, $author, $datefrom, $dateto, $taggedEntity);
        } else {
            $videos = $videoRepo->search(null, $user, self::cantVideos, $offset, null, null, $author, null, null, 'views', $taggedEntity);
            $countAll = $videoRepo->countSearch(null, $user, null, null, $author, null, null, $taggedEntity);
        }

        $response['addMore'] = $countAll > (self::cantVideos * $page) ? true : false;
        $response['elements'] = array();

        foreach ($videos as $video) {
            $response['elements'][] = $this->get('serializer')->values($video);
            /*
            array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'slug' => $video->getSlug(),
                'imgsrc' => $this->getImageUrl($video->getImage(), 'medium'),
                'visitCount' => $video->getVisitCount(),
                'url' => $this->generateUrl('video_show', array(
                    'id' => $video->getId(),
                    'slug' => $video->getSlug()
                ))
            );
            */
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/video/ajax/populars", name="video_popular")
     */
    public function popularVideosAction()
    {
        $request = $this->getRequest();

        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');

        $user = $this->getUser();

        $page = $request->get('page', 1);
        $offset = ($page - 1 ) * self::cantVideos;

        $response = array();

        $videoRepo = $this->getRepository('Video');
        $videoRepo instanceof VideoRepository;

        $taggedEntity = null;
        if ($entityType == 'user') {
            $author = $this->getRepository('User')->find($entityId);
        } else {
            $author = null;

            switch ($entityType) {
                case 'team':
                    $taggedEntity = $this->getRepository('Team')->find($entityId);
                    break;
                case 'idol':
                    $taggedEntity = $this->getRepository('Idol')->find($entityId);
                    break;
            }
        }

        $videos = $videoRepo->search(null, $user, self::cantVideos, $offset, null, null, $author, null, null, 'default', $taggedEntity);
        $countAll = $videoRepo->countSearch(null, $user, null, null, $author, null, null, $taggedEntity);

        $response['addMore'] = $countAll > (self::cantVideos * $page) ? true : false;
        $response['elements'] = array();

        foreach ($videos as $video) {
            $response['elements'][] = $this->get('serializer')->values($video);
            /*
                array(
                    'id' => $video->getId(),
                    'title' => $video->getTitle(),
                    'slug' => $video->getSlug(),
                    'imgsrc' => $this->getImageUrl($video->getImage(), 'medium'),
                    'visitCount' => $video->getVisitCount(),
                    'url' => $this->generateUrl('video_show', array(
                        'id' => $video->getId(),
                        'slug' => $video->getSlug()
                    ))
                );
            */
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/video/ajax/playerurl", name="video_player_url")
     */
    public function playerUrlAction()
    {
        $request = $this->getRequest();
        $id = $request->get('id');

        $video = $this->getRepository('Video')->find($id);

        $this->securityCheck($video);

        return $this->jsonResponse(
            $this->get('flumotiontwig')->getVideoPlayerUrl($video, true)
        );
    }


    /**
     * @Route("/video/ajax/playerfinal", name="video_ajaxPlayerFinalAction")
     */
    public function playerFinalAction()
    {
        $request = $this->getRequest();
        $id = $request->get('id');
        $idVideoDom = $request->get('idVideoDom');
        $video = $this->getRepository('Video')->find($id);

        $idols = array();
        foreach ($video->getHasidols() as $idol) {
                $entidad = $idol->getIdol();
                $this->_createIdolTeamArray($entidad, $idols);
        }

        $teams = array();
        foreach ($video->getHasteams() as $team) {
                $entidad = $team->getTeam();
                $this->_createIdolTeamArray($entidad, $teams);
        }

        $relatedVideos = $this->getRepository('Video')->related($video, null, 5);

        $this->_addMoreData($idols, $relatedVideos, 'idol');
        $this->_addMoreData($teams, $relatedVideos, 'team');

        $response = array(
            'view' => $this->renderView(
                'DodiciFansworldWebBundle:Video:final_action.html.twig',
                    array('idols' => $idols, 'teams' => $teams, 'idvideodom'=> $idVideoDom, 'video' => $video ))
        );

        return $this->jsonResponse($response);
    }

    private function _createIdolTeamArray($entity, &$array) {
        $isFan = $this->get('fanmaker')->isFan($entity, $this->getUser());
        if (!$isFan) {
            array_push($array,
                array(
                    'id' => $entity->getId(),
                    'title' => (string) $entity,
                    'image' => $entity->getImage()
                )
            );
        }
    }

    private function _addMoreData(&$array, $videos, $entityType) {
        if (count($array) < self::MIN_ITEMS_CALLTOACTION) {
            foreach ($videos as $video) {
                if (count($array) < self::MIN_ITEMS_CALLTOACTION) {
                    $getHas = "getHas".$entityType."s"; $get = "get".ucwords($entityType);
                    foreach ($video->$getHas() as $item) {
                        if (count($array) < self::MIN_ITEMS_CALLTOACTION) {
                            $entidad = $item->$get();
                            $isFan = $this->get('fanmaker')->isFan($entidad, $this->getUser());

                            $inarray = false;
                            foreach ($array as $element) {
                                if ($entidad->getId() == $element['id']) $inarray = true;
                            }

                            if (!$isFan && !$inarray) {
                                array_push($array,
                                    array(
                                        'id' => $entidad->getId(),
                                        'title' => (string) $entidad,
                                        'image' => $entidad->getImage()
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}