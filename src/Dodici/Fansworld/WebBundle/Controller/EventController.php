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
use Symfony\Component\HttpFoundation\Request;

/**
 * Event controller.
 * @Route("/event")
 */
class EventController extends SiteController
{
	/**
     * @Route("/{id}/{slug}", name= "event_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     */
    public function showAction($id)
    {
        //TODO: todo
    	$event = $this->getRepository('Event')->find($id);

        $this->securityCheck($event);

        return new Response('TODO');
    }

}
