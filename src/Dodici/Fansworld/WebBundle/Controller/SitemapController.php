<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sitemap controller.
 */
class SitemapController extends SiteController
{
    /**
     * Render sitemap
     * @Route("/sitemap.{_format}", name="sitemap_render", requirements={"_format" = "xml"})
     * @Template("DodiciFansworldWebBundle:Sitemap:sitemap.xml.twig")
     * @Cache(smaxage="36000",maxage="36000")
     */
    public function renderAction()
    {
        $teams = $this->getRepository('Team')->findBy(array('active' => true));
        $idols = $this->getRepository('Idol')->findBy(array('active' => true));
        $users = $this->getRepository('User')->findBy(array('restricted' => false, 'enabled' => true));
        $videos = $this->getRepository('Video')->findBy(array('active' => true, 'privacy' => Privacy::EVERYONE));
        $videocategories = $this->getRepository('VideoCategory')->findAll();
        $photos = $this->getRepository('Photo')->findBy(array('active' => true, 'privacy' => Privacy::EVERYONE));
        
        return array(
            'teams' => $teams, 'idols' => $idols, 'users' => $users, 'videos' => $videos, 'videocategories' => $videocategories,
            'photos' => $photos
        );
    }

}
