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
 * Proposal controller.
 * @Route("/proposal")
 */
class ProposalController extends SiteController
{
	/**
     * @Route("/{id}/{slug}/", name= "proposal_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     */
    public function showAction($id)
    {
        //TODO: todo
    	$proposal = $this->getRepository('Proposal')->find($id);

        $this->securityCheck($proposal);

        return new Response('TODO');
    }

}
