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
class ShareController extends SiteController
{

    /**
     * 
     * @Route("/ajax/share", name="share_ajax")
     */
    public function ajaxShareAction()
    {
        $request = $this->getRequest();

        $entityType = $request->get('entity-type', false);
        $entityId = $request->get('entity-id', false);

        $thingToShare = $this->getRepository($entityType)->find($entityId);

        $toFb = $request->get('fb', false);
        $toTw = $request->get('tw', false);
        $toFw = $request->get('fw', false);

        $defaultMsg = 'Mensaje por defecto enviado desde el backend :D';
        $message = $request->get('message', $defaultMsg);

        if (strlen($message) < 1) {
            $message = $defaultMsg;
        }

        $response = array(
            'error' => false,
            'msg' => 'Sent...'
        );

        $user = $this->getUser();

        if ($user instanceof User) {
            if ($toFb) {
                $facebook = $this->get('app.facebook');
                $facebook instanceof AppFacebook;

                try {
                    $facebook->entityShare($thingToShare, $message);
                } catch (Exception $exc) {
                    $response['error'] = true;
                    $response['msg'] = $exc->getMessage();
                }
            }

            if ($toTw) {
                $twitter = $this->get('app.twitter');
                $twitter instanceof AppTwitter;

                try {
                    $twitter->entityShare($thingToShare, $message);
                } catch (Exception $exc) {
                    $response['error'] = true;
                    $response['msg'] = $exc->getMessage();
                }
            }

            if ($toFw) {
                $sharer = $this->get('sharer');
                try {
                    $sharer->share($thingToShare, null, $message, $this->getUser());
                } catch (Exception $exc) {
                    $response['error'] = true;
                    $response['msg'] = $exc->getMessage();
                }
            }
        } else {
            $response['error'] = true;
            $response['msg'] = 'User is not logged';
        }

        return $this->jsonResponse($response);
    }

}
