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
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;

class SearchController extends SiteController
{

    const LIMIT_SEARCH = 20;

    /**
     * Site's home
     * 
     * @Template()
     */
    public function indexAction()
    {
        return array(
        );
    }

    /**
     * @Route("/search/", name = "search_search")
     * @Template
     */
    public function searchAction()
    {
        return array();
    }

    /**
     *  @Route("/ajax/search/", name = "search_ajaxsearch")
     *  
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page');

        $offset = (int) $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $userRepo = $this->getRepository('User');

        $response = false;
        if ($query) {
            $response = array();
            $user = $this->get('security.context')->getToken()->getUser();

            if ($user instanceof User) {
                $search = $userRepo->SearchFront($user, $query, null, self::LIMIT_SEARCH, $offset);
                $countSearch = $userRepo->CountSearchFront($user, $query, null);

                if ($countSearch > 0) {
                    foreach ($search as $element) {
                        $response['search'][] = array(
                            'id' => $element[0]->getId(),
                            'name' => (string) $element[0],
                            'image' => $this->getImageUrl($element[0]->getImage()),
                            'commonFriends' => $element['commonfriends']
                        );
                    }



                    if ($countSearch > $offset) {
                        $response['gotMore'] = true;
                    } else {
                        $response['gotMore'] = false;
                    }
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
        $user = $this->get('security.context')->getToken()->getUser();

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
        $page = $request->get('page');
        $userRepo = $this->getRepository('User');
        $user = $this->get('security.context')->getToken()->getUser();

        $offset = 0;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $response = false;

        if ($query && $user instanceof User) {
            $response = array();
            $search = $userRepo->FriendUsers($user, $query, self::LIMIT_SEARCH, $offset);
            $countFriendUsers = $userRepo->CountFriendUsers($user, $query);

            if ($countFriendUsers > 0) {
                foreach ($search as $element) {
                    $response['search'][] = array(
                        'id' => $element->getId(),
                        'name' => (string) $element,
                        'image' => $this->getImageUrl($element->getImage())
                    );
                }

                if ($countFriendUsers > $offset) {
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

        $query = $request->get('query', false);
        $isIdol = null;
        $page = (int) $request->get('page', false);

        if ($query && !$page) {
            $page = 0;
        } else {
            $page--;
        }

        $offset = $page;
        if ($page > 0) {
            $offset = $page * self::LIMIT_SEARCH;
        }

        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            if ($query) {
                $response = array();
                $searchIdol = $this->getRepository('User')->SearchIdolFront($user, $query, $isIdol, self::LIMIT_SEARCH, $offset);
                $countTotal = $this->getRepository('User')->CountSearchIdolFront($user, $query, $isIdol);

                if ($countTotal > 0) {
                    $response['gotMore'] = $countTotal * $page > $offset ? true : false;

                    foreach ($searchIdol as $idol) {
                        $response['idols'][] = array(
                            'id' => $idol[0]->getId(),
                            'name' => (string) $idol[0],
                            'image' => $this->getImageUrl($idol[0]->getImage()),
                            'commonFriends' => $idol['commonfriends'],
                            'isidol' => $idol['isidol']
                        );
                    }
                }
            }
        }

        return $this->jsonResponse($response);
    }

}
