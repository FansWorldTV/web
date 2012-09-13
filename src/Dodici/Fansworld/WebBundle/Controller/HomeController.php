<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Dodici\Fansworld\WebBundle\Controller\SiteController;

/**
 * Home controller.
 */
class HomeController extends SiteController
{
    /**
     * Site's home
     * @Template
     */
    public function indexAction()
    {
		$users = $this->getRepository('User')->findBy(array('enabled' => true));
		$teams = $this->getRepository('Team')->findBy(array('active' => true));
		$idols = $this->getRepository('Idol')->findBy(array('active' => true));
		$videos = $this->getRepository('Video')->findBy(array('active' => true));
        
        return array(
            'users' => $users,
            'teams' => $teams,
            'idols' => $idols,
            'videos' => $videos
        );
    }
    
}
