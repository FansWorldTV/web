<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Share;
use Symfony\Component\HttpFoundation\Request;
use Dodici\Fansworld\WebBundle\Extensions\AppFacebook;
use Dodici\Fansworld\WebBundle\Extensions\AppTwitter;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Privacy;

/**
 * Share controller.
 */
class ShareController extends SiteController {

  /**
   * 
   * @Route("/ajax/share", name="share_ajax")
   */
  public function ajaxShareAction() {
    $request = $this->getRequest();

    $toFb = $request->get('toFb', false);
    $toTw = $request->get('toTw', false);

    $message = $request->get('message', 'Mensaje por defecto enviado desde el backend :D');

    $response = array();

    $user = $this->getUser();

    if ($user instanceof User) {
      if ($toFb) {
        $facebook = $this->get('app.facebook');
        $facebook instanceof AppFacebook;

        try {
          $response = $facebook->postFeed($message);
        } catch (Exception $exc) {
          $response['error'] = true;
          $response['msg'] = $exc->getMessage();
        }
      }

      if ($toTw) {
        $twitter = $this->get('app.twitter');
        $twitter instanceof AppTwitter;

        try {
          $twitter->postFeed($message);
        } catch (Exception $exc) {
          $response['error'] = true;
          $response['msg'] = $exc->getMessage();
        }
      }

      try {
        $em = $this->getDoctrine()->getEntityManager();
        $commentInMyWall = new Comment();
        $commentInMyWall->setAuthor($user);
        $commentInMyWall->setTarget($user);
        $commentInMyWall->setActive(true);
        $commentInMyWall->setContent($message);
        $commentInMyWall->setPrivacy(Privacy::FRIENDS_ONLY);
        $em->persist($commentInMyWall);
        $em->flush();
      } catch (Exception $exc) {
        $response['error'] = true;
        $response['msg'] = $exc->getMessage();
      }
    } else {
      $response['error'] = true;
      $response['msg'] = 'User is not logged';
    }


    return $this->jsonResponse($response);
  }

}
