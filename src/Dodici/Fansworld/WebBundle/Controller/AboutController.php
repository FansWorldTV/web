<?php

namespace Dodici\Fansworld\WebBundle\Controller;
use Dodici\Fansworld\WebBundle\Entity\HasUser;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * About controller.
 */
class AboutController extends SiteController
{
    /**
     * terms and condition's view
     * @Route("/terms", name="about_terms")
     * @Template
     */
    public function termsAction()
    {
		
        return array();
    }
    
}
