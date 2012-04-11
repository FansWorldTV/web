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
            $author = $this->get('security.context')->getToken()->getUser();

            if (!($author instanceof User))
                throw new \Exception('Debe iniciar sesión');

            $targetId = $request->get('target');
            $friendgroups = $request->get('friendgroups', array());

            $target = $this->getRepository('User')->find($targetId);

            if (!$this->get('appstate')->canFriend($target))
                throw new \Exception('No puede agregar a esta persona');

            $friendship = new Friendship;
            $friendship->setAuthor($author);
            $friendship->setTarget($target);

            foreach ($friendgroups as $id) {
                $friendgroup = $this->getRepository('FriendGroup')->find($id);
                $friendship->addFriendGroup($friendgroup);
            }


            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($friendship);
            $em->flush();
            $response = array(
                'error' => false,
                'friendship' => $friendship->getId()
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
        $author = $this->get('security.context')->getToken()->getUser();
        $friendshipId = $request->get('friendship');

        $friendship = $this->getRepository('Friendship')->find($friendshipId);

        if ($author->getId() == $friendship->getAuthor()->getId() || $author->getId() == $friendship->getTarget()->getId()) {
            try {
                $em = $this->getDoctrine()->getEntityManager();
                $em->remove($friendship);
                $em->flush();
            } catch (\Exception $exc) {
                $response['error'] = $exc->getMessage();
            }
        } else {
            $response['error'] = 'User is not the author';
        }

        return $this->jsonResponse($response);
    }

    /**
     *  User friendships
     * 
     *  @Route("/friendships/user/{id} ", name="friendship_user")
     *  @Template
     */
    public function userFriendshipsAction($id)
    {
        $userRepo = $this->getRepository('User');
        $user = $userRepo->find($id);

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

}
