<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class UserController extends SiteController
{

    const LIMIT_SEARCH = 20;

    /**
     * @Route("/search/", name = "user_search")
     * @Template
     */
    public function searchAction()
    {
        return array();
    }

    /**
     *  @Route("/ajax/search/", name = "user_ajaxsearch")
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

            if ($user !== "anon.") {
                $search = $userRepo->SearchFront($user, $query, false, self::LIMIT_SEARCH, $offset);

                foreach ($search as $element) {
                    $response['search'][$element[0]->getId()]['id'] = $element[0]->getId();
                    $response['search'][$element[0]->getId()]['name'] = (string) $element[0];
                    $response['search'][$element[0]->getId()]['image'] = $element[0]->getImage();
                    $response['search'][$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                }


                $countSearch = $userRepo->CountSearchFront($user, $query, false, null, $offset);

                if ($countSearch > self::LIMIT_SEARCH) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
            }
        }

        die(json_encode($response));
    }

    /**
     * @Route("/friends/", name="user_friends")
     * @Template a 
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
     *  @Route("/ajax/friends/", name="user_ajaxfriends") 
     */
    public function ajaxFriendsAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page');
        $userRepo = $this->getRepository('User');
        $user = $this->get('security.context')->getToken()->getUser();
        
        $offset = (int) $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $response = false;
        
        if ($query && $user !== "anon.") {
            $response = array();
            $search = $userRepo->FriendUsers($user, $query, false, self::LIMIT_SEARCH, $offset);

            $countFriendUsers = $userRepo->CountFriendUsers($user, $query, false, self::LIMIT_SEARCH, $offset);

            if ($countFriendUsers > 0) {
                foreach ($search as $element) {
                    $response['search'][$element[0]->getId()]['id'] = $element[0]->getId();
                    $response['search'][$element[0]->getId()]['name'] = (string) $element[0];
                    $response['search'][$element[0]->getId()]['image'] = $element[0]->getImage();
                    $response['search'][$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                }

                $search = $userRepo->SearchFront($user, $query, false, null, $offset);
                if ($countFriendUsers > self::LIMIT_SEARCH) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
            }
        }
        
        die(json_encode($response));
    }

    /**
     * @Route("/detail/{id}", name="user_detail", defaults = { "id" = "none" })
     * @Template
     */
    public function detailAction($id)
    {
        if ($id == "none") {
            echo "No existe el usuario";
            exit;
        }

        $user = $this->getRepository('User')->findBy(array('id' => $id));

        return array('user' => $user);
    }

    /**
     * @Route("/register/", name="user_register")
     * @Template
     */
    public function registerAction()
    {
        return array();
    }

}
