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

        $response['friendUsers'] = $this->getRepository('User')->FriendUsers($user);
        
        return $response;
    }

    /**
     * Ajax method 
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

        $trending = $this->get('tagger')->trending(3);


        return $this->jsonResponse(array(
                    'videos' => $serializer->values($videos, 'medium'),
                    'trending' => $trending
                ));
    }

    /**
     * Ajax method
     * @Route("/home/ajax/follow", name="home_ajaxfollow")
     */
    public function ajaxFollowAction()
    {
        $serializer = $this->get('serializer');

        $photo = $this->getRepository('Photo')->areTagged(20);
        $video = $this->getRepository('Video')->areTagged(20);

        $photo = $serializer->values($photo, 'big');
        $video = $serializer->values($video, 'big');

        $elements = array();
        $photoCountAdded = 0;
        $videoCountAdded = 0;


        for ($i = 0; $i < 40; $i++) {
            $isPhoto = rand(0, 1);
            $isPhoto = (bool) $isPhoto;

            if ($isPhoto && $photoCountAdded < 20) {
                if (isset($photo[$photoCountAdded])) {
                    array_push($elements, array(
                        'element' => $photo[$photoCountAdded],
                        'type' => 'photo'
                    ));
                    $photoCountAdded++;
                }
            } else {
                if (isset($video[$videoCountAdded])) {
                    array_push($elements, array(
                        'element' => $video[$videoCountAdded],
                        'type' => 'video'
                    ));
                    $videoCountAdded++;
                }
            }
        }

        return $this->jsonResponse(array(
                    'elements' => $elements
                ));
    }

    /**
     * ajaxMethod
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
     * Ajax method
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

    /**
     * User activity feed
     * @Route("/home/ajax/activity-feed", name="home_ajaxactivityfeed")
     */
    public function ajaxActivityAction()
    {
        $user = $this->getUser();
        $userFeed = $this->get('user.feed');
        $request = $this->getRequest();
        
        $maxDate = $request->get('date', null);
        $filters = $request->get('filters', '0');
        
        switch ($filters) {
            case '0':
                $filters = array('fans', 'idols', 'teams');
                break;
            case '1':
                $filters = array('idols');
                break;
            case '2':
                $filters = array('teams');
                break;
            case '3':
                $filters = array('fans');
                break;
        }
        
        $results = $userFeed->latestActivity(10, $filters, array('photo', 'video'), $maxDate, null, $user, true);
        
        
        return $this->jsonResponse($results);
    }
    
    /**
     * User popular feed
     * @Route("/home/ajax/popular-feed", name="home_ajaxpopularfeed")
     */
    public function ajaxPopularAction()
    {
        $user = $this->getUser();
        $userFeed = $this->get('user.feed');
        $request = $this->getRequest();
        $maxDate = $request->get('date', false);
        
        $results = $userFeed->popular(10, array('photo', 'video'), $maxDate, null, $user, true);
        
        
        return $this->jsonResponse($results);
    }

}
