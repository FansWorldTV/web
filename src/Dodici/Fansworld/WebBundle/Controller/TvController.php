<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Application\Sonata\UserBundle\Entity\Notification;

/**
 * Tv controller.
 * @Route("/tv")
 */
class TvController extends SiteController
{

    const LIMIT_VIDEOS = 6;

    /**
     * @Route("", name="tv_home")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function homeTabAction()
    {
        $user = $this->getUser();
        $channels = array(
            'all' => 'active',
            'lifestyle' ,
            'interviews',
            'tricks',
            'fans',
            'clubs',
            'challenges',
            'historys',
        );
        
        
        $videoRepo = $this->getRepository('Video');
        $videoRepo instanceof VideoRepository;
        
        $videosDestacadosFW = $videoRepo->search(null, null, self::LIMIT_VIDEOS, null, null, null, null, null,null);
        
        $videoPlayerUrl = $videoRepo->search(null, null, 1, null, null, null, null, null,null);
        $tags = array('tag1','otro tag diferente', 'shortag', 'dancing');
        
        return array(
            'user' => $user, 
            'channels' => $channels,
            'videoPlayerUrl' => $videoPlayerUrl,
            'videosDestacadosFW' => $videosDestacadosFW,
            'tags' => $tags,        
       );
       
    }

}
