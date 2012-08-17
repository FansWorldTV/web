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

        $toFb = $request->get('toFb', false);
        $toTw = $request->get('toTw', false);
        
        $response = 'nada';

        $facebook = $this->get('app.facebook');
        $facebook instanceof AppFacebook;

        $response = $facebook->postFeed('caca');

        return $this->jsonResponse($response);
    }

}
