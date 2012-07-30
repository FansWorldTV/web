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
            $friendgroups = $request->get('friendgroups', array());

            $target = $this->getRepository('User')->find($targetId);

            if (!$this->get('appstate')->canFriend($target))
                throw new \Exception('No puede agregar a esta persona');

            $friendship = new Friendship;
            $friendship->setAuthor($author);
            $friendship->setTarget($target);
            if ($target->getRestricted()) {
            	$friendship->setActive(false);
            }

            foreach ($friendgroups as $id) {
                $friendgroup = $this->getRepository('FriendGroup')->find($id);
                $friendship->addFriendGroup($friendgroup);
            }

			
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($friendship);
            $em->flush();
            
            $trans = $this->get('translator');
            if ($friendship->getActive()) {
            	$message = $trans->trans('Ahora sigues a '. $target .'.');
            } else {
            	$message = $trans->trans('Le has enviado una solicitud a ' . $target . ', deberá aprobarla para que puedas seguirlo.');
            }
            
            $response = array(
                'error' => false,
                'friendship' => $friendship->getId(),
            	'active' => $friendship->getActive(),
            	'message' => $message
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
        $author = $this->getUser();
        $friendshipId = $request->get('friendship');

        $friendship = $this->getRepository('Friendship')->find($friendshipId);

        if ($author == $friendship->getAuthor()) {
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

}
