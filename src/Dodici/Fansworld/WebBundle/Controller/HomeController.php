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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Serializer\Serializer;

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
        foreach ($videoCategories as $vc) {
            // el author ( 7mo ) tiene que ir en false
            $videos = $this->getRepository('Video')->search(null, $user, 1, null, $vc, true, null, null, null, null, null, null, null, null, 'DESC', null);
            $video = false;
            foreach ($videos as $vid) {
                $video = $vid;
            }

            $response['categories'][$vc->getId()] = $vc;
            $response['videos'][$vc->getId()] = $video;
        }

        $countUsers = $this->getRepository('User')->countBy(array('enabled' => true));
        $response['totalUsers'] = $countUsers;

        return $response;
    }

    /**
     * @Route("/home/ajax/enjoy", name="home_ajaxenjoy")
     */
    public function ajaxEnjoyAction()
    {
        $serializer = $this->get('serializer');
        $request = $this->getRequest();
        $filter = $request->get('filter', 0);
        $filter = (int) $filter;
        $byTag = $request->get('tag', false);

        $video = $this->getRepository('Video');
        $videos = false;

        if (!$byTag) {
            switch ($filter) {
                case 0:
                    $videos = $video->findBy(array('active' => true), array('weight' => 'DESC'), 20);
                    break;
                case 1:
                    $videos = $video->findBy(array('active' => true, 'highlight' => true), array('weight' => 'DESC'), 20);
                    break;
            }
        } else {
            $tag = $this->getRepository('Tag')->find($byTag);
            $videos = $video->searchByTag(null, null, 20, null, $tag);
        }

        return $this->jsonResponse(array(
                    'videos' => $serializer->values($videos, 'medium')
                ));
    }

    /**
     * @Route("/home/ajax/follow", name="home_ajaxfollow")
     */
    public function ajaxFollowAction()
    {
        $serializer = $this->get('serializer');
        $request = $this->getRequest();
    }

    /**
     * @Route("/home/ajax/connect", name="home_ajaxconnect")
     */
    public function ajaxConnectAction()
    {
        $serializer = $this->get('serializer');
        $fans = $this->getRepository('User')->findBy(array('enabled' => true), array('score' => 'DESC'), 20);

        return $this->jsonResponse(array(
                    'fans' => $serializer->values($fans, 'big_square')
                ));
    }

    /**
     * @Route("/home/ajax/participate", name="home_ajaxparticipate")
     */
    public function ajaxParticipateAction()
    {
        $serializer = $this->get('serializer');
        $events = $this->getRepository('Event')->findBy(array('finished' => false), array('weight' => 'desc'), 20);

        return $this->jsonResponse(array(
                    'events' => $serializer->values($events, 'small')
                ));
    }

}
