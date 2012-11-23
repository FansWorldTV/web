<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Services\Search;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;

class SearchController extends SiteController
{

    const LIMIT_SEARCH_VIDEO = 14;
    const LIMIT_SEARCH_IDOL = 9;
    const LIMIT_SEARCH_USER = 9;
    const LIMIT_SEARCH_PHOTO = 10;
    const LIMIT_SEARCH_EVENT = 4;
    const LIMIT_SEARCH_TEAM = 4;

    /**
     * @Route("/search", name = "search_home")
     * @Template()
     */
    public function indexAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $query = $request->get('query', null);
        
        $videoRepo = $this->getRepository('Video');
        $idolRepo = $this->getRepository('Idol');
        $fanRepo = $this->getRepository('User');
        $photoRepo = $this->getRepository('Photo');
        $eventRepo = $this->getRepository('Event');
        $teamRepo = $this->getRepository('Team');

        $videoSearch = $videoRepo->search($query, $user, self::LIMIT_SEARCH_VIDEO);
        $videoCount = $videoRepo->countSearch($query);

        $idolSearch = $idolRepo->search($query, null, self::LIMIT_SEARCH_IDOL);
        $idolCount = $idolRepo->countSearch($query);

        $fanSearch = $fanRepo->search($query, null, self::LIMIT_SEARCH_USER);
        $fanCount = $fanRepo->countSearch($query);

        $photoSearch = $photoRepo->search($query, null, self::LIMIT_SEARCH_PHOTO);
        $photoCount = $photoRepo->countSearch($query);

        $eventSearch = $eventRepo->search($query, null, self::LIMIT_SEARCH_EVENT);
        $eventCount = $eventRepo->countSearch($query);

        $teamSearch = $teamRepo->search($query, null, self::LIMIT_SEARCH_TEAM);
        $teamCount = $teamRepo->countSearch($query);

        $todo = $videoCount + $idolCount + $fanCount + $photoCount + $eventCount;

        $idols = array(
            'ulClass' => 'idols',
            'containerClass' => 'idol-container',
            'list' => $idolSearch
        );

        $fans = array(
            'ulClass' => 'fans',
            'containerClass' => 'fan-container',
            'list' => array()
        );

        $teams = array(
            'ulClass' => 'teams',
            'containerClass' => 'team-container',
            'list' => $teamSearch
        );

        foreach ($fanSearch as $fan) {
            $fans['list'][] = $fan[0];
        }
        
        $trending = $this->get('tagger')->trending();
        
        $videosHighlighted = $this->getRepository('Video')->findBy(array('highlight' => true, 'active' => true), array('weight' => 'desc'), 2);

        return array(
            'todoCount' => $todo,
            'videoCount' => $videoCount,
            'idolCount' => $idolCount,
            'fanCount' => $fanCount,
            'photoCount' => $photoCount,
            'eventCount' => $eventCount,
            'teamCount' => $teamCount,
            'idols' => $idols,
            'events' => $eventSearch,
            'photos' => $photoSearch,
            'fans' => $fans,
            'videos' => $videoSearch,
            'teams' => $teams,
            'query' => $query,
            'trending' => $trending,
            'videosHighlighted' => $videosHighlighted
        );
    }

    /**
     *  @Route("/ajax/search", name="search_ajaxsearch")
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();

        $query = $request->get('query', null);
        $page = $request->get('page', 1);
        $type = $request->get('type', false);

        if ($type) {
            $limit = constant('self::LIMIT_SEARCH_' . strtoupper($type));
        } else {
            $limit = null;
        }

        $page = ((int) $page );
        $offset = ( $page - 1 ) * $limit;

        $response = array();

        $searcher = $this->get('search');
        $searcher instanceof Search;

        $searchType = constant('Dodici\Fansworld\WebBundle\Services\Search::' . strtoupper('TYPE_' . $type));

        $search = $searcher->search($query, $searchType, null, $limit, $offset);

        $countAll = $searcher->count($query, $searchType);

        $serializer = $this->get('serializer');

        switch ($type) {
            case 'video':
                $imageSize = "huge_square";
                break;
            default :
                $imageSize = "medium";
                break;
        }

        $response['search'] = $serializer->values($search, $imageSize);

        if ($response['search']) {
            foreach ($response['search'] as $key => $el) {
                if (array_key_exists('duration', $el)) {
                    $response['search'][$key]['duration'] = date('i:s', $el['duration']);
                }

                $entity = $this->getRepository(ucfirst($type))->find($el['id']);
                
                switch ($type) {
                    case 'event':
                        if ($this->getUser() instanceof User) {
                            $response['search'][$key]['checked'] = $this->getRepository('Eventship')->findOneBy(array('author' => $this->getUser()->getId(), 'event' => $el['id'])) ? true : false;
                        } else {
                            $response['search'][$key]['checked'] = null;
                        }
                        $now = new \DateTime();
                        $started = ($entity->getFromtime() <= $now);

                        $response['search'][$key]['text'] = $this->get('appstate')->getEventText($el['id']);
                        $response['search'][$key]['date'] = $entity->getFromtime()->format('d-m-Y');
                        $response['search'][$key]['showdate'] = $entity->getFromtime()->format('d/m/Y H:i');
                        $response['search'][$key]['url'] = $this->generateUrl('event_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()));
                        $response['search'][$key]['started'] = $started;
                        
                        break;

                    case 'video':
                        $response['search'][$key]['url'] = $this->generateUrl('video_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()));
                        break;
                    
                    case 'idol':
                        $response['search'][$key]['url'] = $this->generateUrl('idol_wall', array('slug' => $entity->getSlug()));
                        break;
                    
                    case 'user':
                        $response['search'][$key]['url'] = $this->generateUrl('user_wall', array('slug' => $entity->getUsername()));
                        break;
                    
                    case 'photo':
                        $response['search'][$key]['url'] = $this->generateUrl('photo_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()));
                        break;
                    
                    case 'team':
                        $response['search'][$key]['url'] = $this->generateUrl('team_wall', array('slug' => $entity->getSlug()));
                        break;
                    
                }
            }
        }

        $response['addMore'] = $countAll > ($limit * $page) ? true : false;

        return $this->jsonResponse($response);
    }

//     METODOS EN DESUSO
//    /**
//     * @Route("/search/fans", name = "search_search")
//     * @Template
//     */
//    public function searchAction()
//    {
//        $request = $this->getRequest();
//        $query = $request->get('query');
//        $user = $this->getUser();
//        $response = array();
//
//        if ($query && $user instanceof User) {
//            $search = $this->getRepository('User')->searchFront($user, $query, null, self::LIMIT_SEARCH);
//
//            foreach ($search as $element) {
//                $response['search'][] = array(
//                    'id' => $element[0]->getId(),
//                    'username' => $element[0]->getUsername(),
//                    'name' => (string) $element[0],
//                    'image' => $this->getImageUrl($element[0]->getImage()),
//                    'commonFriends' => $element['commonfriends'],
//                    'isFriend' => $element['isfriend']
//                );
//            }
//        }
//
//        return array($response);
//    }
//
//    /**
//     *  @Route("/ajax/search/", name = "search_ajaxsearch")
//     *  
//     */
//    public function ajaxSearchAction()
//    {
//        $request = $this->getRequest();
//        $query = $request->get('query');
//        $page = $request->get('page', 1);
//
//        $page = (int) $page;
//        if ($page > 0) {
//            $offset = ($page - 1) * self::LIMIT_SEARCH;
//        }
//
//        $userRepo = $this->getRepository('User');
//
//        $response = false;
//        $response = array();
//        $user = $this->getUser();
//
//        $query = $query == '' ? null : $query;
//
//        if ($user instanceof User) {
//            $search = $userRepo->SearchFront($user, $query, null, self::LIMIT_SEARCH, $offset);
//            $countSearch = $userRepo->CountSearchFront($user, $query, null);
//
//            if ($countSearch > 0) {
//                foreach ($search as $element) {
//                    $response['search'][] = array(
//                        'id' => $element[0]->getId(),
//                        'username' => $element[0]->getUsername(),
//                        'name' => (string) $element[0],
//                        'image' => $this->getImageUrl($element[0]->getImage()),
//                        'commonFriends' => $element['commonfriends'],
//                        'isFriend' => $element['isfriend']
//                    );
//                }
//
//
//                if (($countSearch / self::LIMIT_SEARCH) > $page) {
//                    $response['gotMore'] = true;
//                } else {
//                    $response['gotMore'] = false;
//                }
//            }
//        }
//
//        $response = new Response(json_encode($response));
//        $response->headers->set('Content-Type', 'application/json');
//        return $response;
//    }
//
//    /**
//     * @Route("/search/friends/", name="search_friends")
//     * @Template
//     */
//    public function friendsAction()
//    {
//        $userRepo = $this->getRepository('User');
//        $user = $this->getUser();
//
//        $friends = $userRepo->FriendUsers($user, null, self::LIMIT_SEARCH, null);
//
//        $canAddMore = false;
//        if ($userRepo->CountFriendUsers($user) > self::LIMIT_SEARCH) {
//            $canAddMore = true;
//        }
//        return array('friends' => $friends, 'canAddMore' => $canAddMore);
//    }
//
//    /**
//     *  @Route("/ajax/friends/", name="search_ajaxfriends") 
//     */
//    public function ajaxFriendsAction()
//    {
//        $request = $this->getRequest();
//        $query = $request->get('query');
//        $page = $request->get('page', 1);
//        $userRepo = $this->getRepository('User');
//
//        $userId = $request->get('userid', false);
//        if ($userId) {
//            $user = $userRepo->find($userId);
//        } else {
//            $user = $this->getUser();
//        }
//
//        $page = (int) $page;
//
//        if ($page > 1) {
//            $offset = ($page - 1) * self::LIMIT_SEARCH;
//        } else {
//            $offset = 0;
//        }
//
//        $query = $query == '' ? null : $query;
//
//        $response = false;
//
//        if ($user instanceof User) {
//            $response = array();
//            $search = $userRepo->FriendUsers($user, $query, self::LIMIT_SEARCH, $offset);
//            $countFriendUsers = $userRepo->CountFriendUsers($user, $query);
//
//            if ($countFriendUsers > 0) {
//                foreach ($search as $element) {
//                    $response['search'][] = array(
//                        'id' => $element->getId(),
//                        'username' => $element->getUsername(),
//                        'name' => (string) $element,
//                        'image' => $this->getImageUrl($element->getImage())
//                    );
//                }
//                if (($countFriendUsers / self::LIMIT_SEARCH) > $page) {
//                    $response['gotMore'] = true;
//                } else {
//                    $response['gotMore'] = false;
//                }
//            }
//        }
//
//        $response = new Response(json_encode($response));
//        $response->headers->set('Content-Type', 'application/json');
//        return $response;
//    }
//
//    /**
//     * Search Idols View
//     * 
//     * @Route("/search/idols", name = "search_idols")
//     * @Template()
//     */
//    public function idolsAction()
//    {
//        return array();
//    }
//        
//   /**
//     * @Route("/search_box/{type}/{query}/{page}", defaults={"type" =  false, "query" = false, "page" = 1}, name="search_box")
//     * @Template()
//     */
//    public function searchBoxAction($type, $query, $page)
//    {
//        $searcher = $this->get('search');
//        $searcher instanceof Search;
//        $page = ((int) $page) - 1;
//        $offset = $page * self::LIMIT_SEARCH;
//
//        $type = (int) $type;
//
//        $types = $searcher->getTypes();
//
//        $search = $searcher->search($query, $type, self::LIMIT_SEARCH, $offset);
//        $countAll = $searcher->count($query, $type);
//
//        return array(
//            'search' => $search,
//            'addMore' => $countAll > self::LIMIT_SEARCH ? true : false,
//            'searchQuery' => $query,
//            'searchType' => $type
//        );
//    }
//    
//    /**
//     * Search Idols Ajax method
//     * @Route("/search/idols/ajax", name = "search_ajaxidols")
//     */
//    public function ajaxIdolsAction()
//    {
//        $request = $this->getRequest();
//        $response = false;
//
//        $query = $request->get('query', null);
//        $isIdol = null;
//        $page = (int) $request->get('page', 1);
//
//        if ($query == "") {
//            $query = null;
//        }
//
//        if ($page > 1) {
//            $offset = ($page - 1) * self::LIMIT_SEARCH;
//        } else {
//            $offset = 0;
//        }
//
//        $user = $this->getUser();
//        if ($user instanceof User) {
//            $response = array();
//            $searchIdol = $this->getRepository('Idol')->SearchFront($user, $query, $isIdol, self::LIMIT_SEARCH, $offset);
//            $countTotal = $this->getRepository('Idol')->CountSearchFront($user, $query, $isIdol);
//
//            if ($countTotal > 0) {
//                $response['gotMore'] = ($countTotal / self::LIMIT_SEARCH) > $page ? true : false;
//
//                foreach ($searchIdol as $idol) {
//                    $response['idols'][] = array(
//                        'id' => $idol[0]->getId(),
//                        'slug' => $idol[0]->getSlug(),
//                        'name' => (string) $idol[0],
//                        'image' => $this->getImageUrl($idol[0]->getImage())
//                    );
//                }
//            }
//        }
//
//        return $this->jsonResponse($response);
//    }
}
