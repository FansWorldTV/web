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
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page');

        $offset = $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $userRepo = $this->getRepository('User');

        if ($query) {
            $user = $this->get('security.context')->getToken()->getUser();
            if ($user !== "anon.") {
                $response = array();
                $search = $userRepo->SearchFront($user, $query, false, self::LIMIT_SEARCH, $offset);

                if (count($search) > 0) {
                    foreach ($search as $element) {
                        $response[$element[0]->getId()]['id'] = $element[0]->getId();
                        $response[$element[0]->getId()]['name'] = (string) $element[0];
                        $response[$element[0]->getId()]['image'] = $element[0]->getImage();
                        $response[$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                    }


                    $search = $userRepo->SearchFront($user, $query, false, null, $offset);
                    $countSearch = $userRepo->CountSearchFront($user, $query, false, null, $offset);

                    if ($countSearch > self::LIMIT_SEARCH) {
                        $response['gotMore'] = true;
                    } else {
                        $response['gotMore'] = false;
                    }
                    return new Response(json_encode($response));
                } else {
                    return new Response(json_encode(false));
                }
            }
        }

        return array();
    }

    /**
     * @Route("/friends/", name="user_friends")
     * @Template 
     */
    public function friendsAction()
    {
        $request = $this->getRequest();
        $query = $request->get('query');
        $page = $request->get('page');
        $userRepo = $this->getRepository('User');
        $user = $this->get('security.context')->getToken()->getUser();

        if ($query && $user !== "anon.") {
            $response = array();
            $search = $userRepo->FriendUsers($user, $query, false, self::LIMIT_SEARCH, $offset);

            $countFriendUsers = $userRepo->CountFriendUsers($user, $query, false, self::LIMIT_SEARCH, $offset);

            if ($countFriendUsers > 0) {
                foreach ($search as $element) {
                    $response[$element[0]->getId()]['id'] = $element[0]->getId();
                    $response[$element[0]->getId()]['name'] = (string) $element[0];
                    $response[$element[0]->getId()]['image'] = $element[0]->getImage();
                    $response[$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                }

                $search = $userRepo->SearchFront($user, $query, false, null, $offset);
                if ($countFriendUsers > self::LIMIT_SEARCH) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
                return new Response(json_encode($response));
            } else {
                return new Response(json_encode(false));
            }
        }

        $friends = $userRepo->FriendUsers($user, null, self::LIMIT_SEARCH, null);

        $canAddMore = false;
        if ($userRepo->CountFriendUsers($user) > self::LIMIT_SEARCH) {
            $canAddMore = true;
        }
        return array('friends' => $friends, 'canAddMore' => $canAddMore);
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
