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
     * terms and conditions view
     * @Route("/terms/{format}", name="about_terms", defaults = {"format" = null})
     * @Template
     */
    public function termsAction($format = null)
    {
        switch($format){
            case 'text':
                    return $this->render('DodiciFansworldWebBundle:About:terms_mobile.html.twig');
                break;
            case 'beta':
                    return $this->render('DodiciFansworldWebBundle:About:terms_beta.html.twig');
                break;
            default:
                return array();
        }
    }
}
