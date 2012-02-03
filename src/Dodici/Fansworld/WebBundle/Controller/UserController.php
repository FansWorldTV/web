<?php

namespace Dodici\Fansworld\WebBundle\Controller;

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
        $offset = ($page - 1) * self::LIMIT_SEARCH;

        if ($query) {
            $user = "";
            $response = array();
            $search = $this->getRepository('User')->SearchFront($user, $query, false, self::LIMIT_SEARCH, $offset);

            if (count($search) > 0) {
                foreach ($search as $element) {
                    $response[$element->getId()]['id'] = $element->getId();
                    $response[$element->getId()]['name'] = (string) $element;
                    $response[$element->getId()]['image'] = $element->getImage();
                    $response[$element->getId()]['commonFriends'] = $element->commonfriends;
                }


                $search = $this->getRepository('User')->SearchFront($user, $query, false, null, $offset);
                if (count($search) > self::LIMIT_SEARCH) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
                return new Response($this->encodeStructure($response));
            } else {
                return new Response($this->encodeStructure(false));
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
        $user = "";
        

        if ($query) {
            $response = array();
            $search = $userRepo->FriendUsers($user, $query, false, self::LIMIT_SEARCH, $offset);

            if (count($search) > 0) {
                foreach ($search as $element) {
                    $response[$element->getId()]['id'] = $element->getId();
                    $response[$element->getId()]['name'] = (string) $element;
                    $response[$element->getId()]['image'] = $element->getImage();
                    $response[$element->getId()]['commonFriends'] = $element->commonfriends;
                }

                $search = $userRepo->SearchFront($user, $query, false, null, $offset);
                if (count($search) > self::LIMIT_SEARCH) {
                    $response['gotMore'] = true;
                } else {
                    $response['gotMore'] = false;
                }
                return new Response($this->encodeStructure($response));
            } else {
                return new Response($this->encodeStructure(false));
            }
        }

        $friends = $userRepo->FriendUsers($user, null, self::LIMIT_SEARCH, null);
        
        $canAddMore = false;
        if($userRepo->CountFriendUsers($user)>self::LIMIT_SEARCH){
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
        if($id == "none"){
            echo "No existe el usuario";exit;
        }
        
        $user = $this->getRepository('User')->findBy(array('id' => $id));
        
        return array('user' => $user);
        
    }

}
