<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Application\Sonata\UserBundle\Entity\Notification;
use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Dodici\Fansworld\WebBundle\Entity\FriendGroup;

class FriendshipController extends SiteController
{

    /**
     * Add friend
     *
     *  @Route("/ajax/add-friend", name="friendship_ajaxaddfriend")
     */
    public function ajaxAddFriendAction()
    {
        try {
            $response = null;
            $request = $this->getRequest();
            $author = $this->getUser();

            if (!($author instanceof User))
                throw new \Exception('Debe iniciar sesión');

            $targetId = $request->get('target');
            $friendgroupids = $request->get('friendgroups', array());

            $target = $this->getRepository('User')->find($targetId);

            if ($target->getType() == User::TYPE_STAFF) throw new \Exception('El usuario es parte de nuestro equipo');

            $friendgroups = array();
            foreach ($friendgroupids as $id) {
                $friendgroup = $this->getRepository('FriendGroup')->find($id);
                $friendgroups[] = $friendgroup;
            }

            $friendship = $this->get('friender')->friend($target, $friendgroups);

            $trans = $this->get('translator');
            if ($friendship->getActive()) {
                $message = $trans->trans('Ahora sigues a ' . $target . '.');
                $buttontext = $trans->trans('YA ERES FAN');
            } else {
                $message = $trans->trans('Le has enviado una solicitud a ' . $target . ', deberá aprobarla para que puedas seguirlo.');
                $buttontext = $trans->trans('CANCELAR SOLICITUD');
            }

            $response = array(
                'error' => false,
                'friendship' => $friendship->getId(),
                'active' => $friendship->getActive(),
                'message' => $message,
                'buttontext' => $buttontext
            );
        } catch (\Exception $exc) {
            $response = array(
                'error' => $exc->getMessage()
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * Delete friendship
     *
     * @Route("/ajax/cancel-friend", name="friendship_ajaxcancelfriend")
     */
    public function ajaxCancelFriendshipAction()
    {
        $response = array('error' => false);

        $request = $this->getRequest();
        $me = $this->getUser();
        $userId = $request->get('user', false);
        $friendshipId = $request->get('friendship', false);

        if ($userId) {
            $mine = $this->getRepository('Friendship')->findBy(array('author' => $me->getId(), 'target' => $userId));
            if ($me->getRestricted()) {
                $friendships = array();
                array_push($friendships, $mine);

                $him = $this->getRepository('Friendship')->findBy(array('author' => $userId, 'target' => $me->getId()));
                array_push($friendships, $him);
            } else {
                $friendships = $mine;
            }
        }else{
            $friendships = $this->getRepository('Friendship')->find($friendshipId);
        }

        try {
            foreach($friendships as $friendship){
                $this->get('friender')->remove($friendship);
            }
        } catch (\Exception $exc) {
            $response['error'] = $exc->getMessage();
        }

        return $this->jsonResponse($response);
    }

    /**
     *  User friendships
     *
     *  @Route("/su/{username}/fans", name="friendship_user")
     *  @Template
     */
    public function userFriendshipsAction($username)
    {
        $userRepo = $this->getRepository('User');
        $user = $userRepo->findOneByUsername($username);

        if (!($user instanceof User))
            throw new \Exception('El usuario no existe.');

        $friends = $userRepo->FriendUsers($user, null, SearchController::LIMIT_SEARCH, null);

        $canAddMore = false;
        if ($userRepo->CountFriendUsers($user) > SearchController::LIMIT_SEARCH) {
            $canAddMore = true;
        }

        return array(
            'user' => $user,
            'friends' => $friends,
            'canAddMore' => $canAddMore
        );
    }


    /**
     * Accept a friendship
     *
     * @Route("/ajax/accept-friendship", name="friendship_accept")
     */
    public function ajaxAcceptFriendshipAction()
    {
        $response = false;
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);

        if ($friendshipId) {
            $response = true;
            $friendship = $this->getRepository('Friendship')->find($friendshipId);
            $this->get('friender')->accept($friendship);
        }

        return $this->jsonResponse($response);
    }


    /**
     * Reject a friendship
     *
     * @Route("/ajax/reject-friendship", name="friendship_reject")
     */
    public function ajaxRejectFriendshipAction()
    {
        $response = false;
        $request = $this->getRequest();
        $friendshipId = $request->get('id', false);

        if ($friendshipId) {
            $response = true;
            $friendship = $this->getRepository('Friendship')->find($friendshipId);
            $this->get('friender')->remove($friendship);
        }

        return $this->jsonResponse($response);
    }

}
