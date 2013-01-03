<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Application\Sonata\UserBundle\Entity\User;

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
        
        $user = $this->getUser();
        $response = array(
            'categories' => array(),
            'videos' => array()
        );
        
        $videoCategories = $this->getRepository('VideoCategory')->findAll();
        foreach ($videoCategories as $vc){
            // el author ( 7mo ) tiene que ir en false
            $videos = $this->getRepository('Video')->search(null, $user, 1, null, $vc, true, null, null, null, null, null, null, null, null, 'DESC', null);
            $video = false;
            foreach($videos as $vid) {
                $video = $vid;
            }
            
            $response['categories'][$vc->getId()] = $vc;
            $response['videos'][$vc->getId()] = $video;
        }
        
        $countUsers = $this->getRepository('User')->countBy(array('enabled' => true));
        
        $response['totalUsers'] = $countUsers;
        
        return $response;
    }

}
