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

    const LIMIT_SEARCH = 20;

    /**
     * Site's home
     * @Template()
     */
    public function indexAction()
    {
        return array(
        );
    }

    /**
     * @Route("/search/fans", name = "search_search")
     * @Template
     */
    public function searchAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $user = $this->getUser();
        $response = array();

        if ($query && $user instanceof User) {
            $search = $this->getRepository('User')->searchFront($user, $query, null, self::LIMIT_SEARCH);

            foreach ($search as $element) {
                $response['search'][] = array(
                    'id' => $element[0]->getId(),
                    'username' => $element[0]->getUsername(),
                    'name' => (string) $element[0],
                    'image' => $this->getImageUrl($element[0]->getImage()),
                    'commonFriends' => $element['commonfriends'],
                    'isFriend' => $element['isfriend']
                );
            }
        }

        return array($response);
    }

    /**
     *  @Route("/ajax/search/", name = "search_ajaxsearch")
     *  
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page', 1);

        $page = (int) $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $userRepo = $this->getRepository('User');

        $response = false;
        $response = array();
        $user = $this->getUser();

        $query = $query == '' ? null : $query;

        if ($user instanceof User) {
            $search = $userRepo->SearchFront($user, $query, null, self::LIMIT_SEARCH, $offset);
            $countSearch = $userRepo->CountSearchFront($user, $query, null);

            if ($countSearch > 0) {
                foreach ($search as $element) {
                    $response['search'][] = array(
                        'id' => $element[0]->getId(),
                        'username' => $element[0]->getUsername(),
                        'name' => (string) $element[0],
                        'image' => $this->getImageUrl($element[0]->getImage()),
                        'commonFriends' => $element['commonfriends'],
                        'isFriend' => $element['isfriend']
                    );
                }


                if (($countSearch / self::LIMIT_SEARCH) > $page) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
            }
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/search/friends/", name="search_friends")
     * @Template
     */
    public function friendsAction()
    {
        $userRepo = $this->getRepository('User');
        $user = $this->getUser();

        $friends = $userRepo->FriendUsers($user, null, self::LIMIT_SEARCH, null);

        $canAddMore = false;
        if ($userRepo->CountFriendUsers($user) > self::LIMIT_SEARCH) {
            $canAddMore = true;
        }
        return array('friends' => $friends, 'canAddMore' => $canAddMore);
    }

    /**
     *  @Route("/ajax/friends/", name="search_ajaxfriends") 
     */
    public function ajaxFriendsAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page', 1);
        $userRepo = $this->getRepository('User');

        $userId = $request->get('userid', false);
        if ($userId) {
            $user = $userRepo->find($userId);
        } else {
            $user = $this->getUser();
        }

        $page = (int) $page;

        if ($page > 1) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        } else {
            $offset = 0;
        }

        $query = $query == '' ? null : $query;

        $response = false;

        if ($user instanceof User) {
            $response = array();
            $search = $userRepo->FriendUsers($user, $query, self::LIMIT_SEARCH, $offset);
            $countFriendUsers = $userRepo->CountFriendUsers($user, $query);

            if ($countFriendUsers > 0) {
                foreach ($search as $element) {
                    $response['search'][] = array(
                        'id' => $element->getId(),
                        'username' => $element->getUsername(),
                        'name' => (string) $element,
                        'image' => $this->getImageUrl($element->getImage())
                    );
                }
                if (($countFriendUsers / self::LIMIT_SEARCH) > $page) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
            }
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Search Idols View
     * 
     * @Route("/search/idols", name = "search_idols")
     * @Template()
     */
    public function idolsAction()
    {
        return array();
    }

    /**
     * Search Idols Ajax method
     * @Route("/search/idols/ajax", name = "search_ajaxidols")
     */
    public function ajaxIdolsAction()
    {
        $request = $this->getRequest();
        $response = false;

        $query = $request->get('query', null);
        $isIdol = null;
        $page = (int) $request->get('page', 1);

        if ($query == "") {
            $query = null;
        }

        if ($page > 1) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        } else {
            $offset = 0;
        }

        $user = $this->getUser();
        if ($user instanceof User) {
            $response = array();
            $searchIdol = $this->getRepository('Idol')->SearchFront($user, $query, $isIdol, self::LIMIT_SEARCH, $offset);
            $countTotal = $this->getRepository('Idol')->CountSearchFront($user, $query, $isIdol);
            
            if ($countTotal > 0) {
                $response['gotMore'] = ($countTotal / self::LIMIT_SEARCH) > $page ? true : false;

                foreach ($searchIdol as $idol) {
                    $response['idols'][] = array(
                        'id' => $idol[0]->getId(),
                        'slug' => $idol[0]->getSlug(),
                        'name' => (string) $idol[0],
                        'image' => $this->getImageUrl($idol[0]->getImage())
                    );
                }
            }
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/search_box/{type}/{query}/{page}", defaults={"type" =  false, "query" = false, "page" = 1}, name="search_box")
     * @Template()
     */
    public function searchBoxAction($type, $query, $page)
    {
        $searcher = $this->get('search');
        $searcher instanceof Search;
        $page = ((int) $page) - 1;
        $offset = $page * self::LIMIT_SEARCH;

        $type = (int) $type;
        
        $types = $searcher->getTypes();
        
        $search = $searcher->search($query, $type, self::LIMIT_SEARCH, $offset);
        $countAll = $searcher->count($query, $type);

        return array(
            'search' => $search,
            'addMore' => $countAll > self::LIMIT_SEARCH ? true : false,
            'searchQuery' => $query,
            'searchType' => $type
        );
    }

    /**
     *  @Route("/ajax/search_box", name="search_ajaxbox")
     */
    public function ajaxSearchBoxAction()
    {
        $request = $this->getRequest();

        $query = $request->get('query', null);
        $page = $request->get('page', 1);
        $type = $request->get('type', false);

        $page = ((int) $page );
        $offset = ( $page - 1 ) * self::LIMIT_SEARCH;

        $response = array();

        $searcher = $this->get('search');
        $searcher instanceof Search;
        $search = $searcher->search($query, $type, self::LIMIT_SEARCH, $offset);

        $countAll = $searcher->count($query, $type);
        $response['addMore'] = $countAll > (self::LIMIT_SEARCH * $page) ? true : false;

        foreach ($search as $element) {
            $response['search'][] = array(
                'title' => $element->getTitle(),
                'content' => $element->getContent()
            );
        }

        return $this->jsonResponse($response);
    }

}
