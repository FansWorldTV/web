<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;

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

            if ($user instanceof User) {
                $search = $userRepo->SearchFront($user, $query, false, self::LIMIT_SEARCH, $offset);

                foreach ($search as $element) {
                    $response['search'][$element[0]->getId()]['id'] = $element[0]->getId();
                    $response['search'][$element[0]->getId()]['name'] = (string) $element[0];
                    $response['search'][$element[0]->getId()]['image'] = $this->getImageUrl($element[0]->getImage());
                    $response['search'][$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                }


                $countSearch = $userRepo->CountSearchFront($user, $query);

                if ($countSearch > $offset) {
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
     * @Route("/friends/", name="user_friends")
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

        if ($query && $user instanceof User) {
            $response = array();
            $search = $userRepo->FriendUsers($user, $query, false, self::LIMIT_SEARCH, $offset);

            $countFriendUsers = $userRepo->CountFriendUsers($user, $query);

            if ($countFriendUsers > 0) {
                foreach ($search as $element) {
                    $response['search'][$element[0]->getId()]['id'] = $element[0]->getId();
                    $response['search'][$element[0]->getId()]['name'] = (string) $element[0];
                    $response['search'][$element[0]->getId()]['image'] = $this->getImageUrl($element[0]->getImage());
                    $response['search'][$element[0]->getId()]['commonFriends'] = $element['commonfriends'];
                }

                $search = $userRepo->SearchFront($user, $query, false, null, $offset);
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
     * @Route("/user/{id}", name="user_detail", requirements={"id"="\d+"})
     * @Template
     */
    public function detailAction($id)
    {


        $user = $this->getRepository('User')->find($id);
        if (!$user) {
            echo "No existe el usuario";
            exit;
        }

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

    /**
     *  @Route("/ajax/number-friend-requests/", name="user_ajaxnumberofpendingrequests") 
     */
    public function ajaxNumberOfPendingRequests()
    {
        $friendRepo = $this->getRepository('Friendship');

        $response = false;

        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $countTotal = $friendRepo->CountPending($user);

            $response = array('number' => $countTotal);
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     *  @Route("/ajax/pending-friends/", name = "user_ajaxpendingfriends")
     *  
     */
    public function ajaxPendingFriendsAction()
    {
        $request = $this->getRequest();
        $limit = $request->get('limit', self::LIMIT_SEARCH);
        $page = $request->get('page', 1);

        $offset = (int) $page;
        if ($page > 0) {
            $offset = ($page - 1) * self::LIMIT_SEARCH;
        }

        $friendRepo = $this->getRepository('Friendship');

        $response = false;

        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $response = array();
            $pending = $friendRepo->Pending($user, $limit, $offset);
            $countTotal = $friendRepo->CountPending($user);
            $mediaservice = $this->get('sonata.media.pool');

            $response['total'] = $countTotal;

            if ($countTotal > ($page * $limit)) {
                $response['gotMore'] = true;
            } else {
                $response['gotMore'] = false;
            }

            foreach ($pending as $element) {
                $media = $element->getAuthor()->getImage();
                $response['friendships'][] = array(
                    'friendship' => array(
                        'id' => $element->getId(),
                        'ts' => $element->getCreatedat()->format('U')
                    ),
                    'user' => array(
                        'id' => $element->getAuthor()->getId(),
                        'name' => (string) $element->getAuthor(),
                        'image' => $this->getImageUrl($media),
                        'url' => $this->generateUrl('user_detail', array('id' => $element->getAuthor()->getId()))
                    )
                );
            }
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    /**
     * @Route("/ajax/accept-request/", name = "user_ajaxacceptrequest")
     */
    public function acceptRequestAction()
    {
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);

        $error = true;
        
        if ($friendshipId) {
            try {
                $friendshipRepo = $this->getRepository('Friendship');
                $friendship = $friendshipRepo->findOneBy(array('id' => $friendshipId));
                $friendship->setActive(true);
                
                $error = false;
            } catch (Exception $exc) {
                $error = $exc->getMessage();
            }
        }
        
        $response = new Response(json_encode(array('error' => $error)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
