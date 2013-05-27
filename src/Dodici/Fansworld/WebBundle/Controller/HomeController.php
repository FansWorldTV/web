<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\VideoCategory;
use Symfony\Component\Config\Definition\Exception\Exception;
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
    const LIMIT_VIDEO = 12;

    /**
     * Site's home
     * @Template
     */
    public function indexAction()
    {
        $checkfbreq = $this->checkFacebookRequest();
        if ($checkfbreq) return $checkfbreq;

        return array(
            'testvideos' => $this->getRepository('Video')->findBy(array(), array(), 10),
            'categories' => $this->getRepository('VideoCategory')->findAll()
        );
    }

    /**
     * Ajax method
     * @Route("/home/ajax/filter", name="home_ajaxfilter")
     */
    public function ajaxFilterAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $serializer = $this->get('serializer');

        $paginate = $request->get('paginate', false);

        $videoRepo = $this->getRepository('Video');

        if(!$paginate){
            $vc = $request->get('vc', false);
            $vc = $this->getRepository('VideoCategory')->find($vc);

            if(!$vc instanceof VideoCategory){
                throw new Exception("No video category");
            }

            $response = array(
                'home' => null,
                'highlighted' => array(),
                'followed' => array(),
                'popular' => array()
            );

            $homeVideo = $this->getRepository('HomeVideo')->findOneBy(array('videocategory' => $vc->getId()));
            $homeVideo = $homeVideo->getVideo();
            $response['home'] = $serializer->values($homeVideo, 'home_video_double');

            $limitWithTheHighlighted = (self::LIMIT_VIDEO-3);
            $videos = $videoRepo->search(null, null, $limitWithTheHighlighted, 0, $vc, true, null, null, null, null, null, $homeVideo);
            $response['highlighted'] = $serializer->values($videos, 'home_video');

            $videos = $videoRepo->search(null, $user, self::LIMIT_VIDEO, 0, $vc->getId(), true, null, null, null, 'default', null, $homeVideo);
            $response['followed'] = $serializer->values($videos, 'home_video');

            $videos = $videoRepo->search(null, null, self::LIMIT_VIDEO, 0, $vc->getId(), false);
            $response['popular'] = $serializer->values($videos, 'home_video');
        }else{
            $vc = $this->getRepository('VideoCategory')->find($paginate['vc']);
            $block = $paginate['block'];
            $page = $paginate['page'];
            $offset = ($page -1)*self::LIMIT_VIDEO;
            $homeVideo = $this->getRepository('HomeVideo')->findOneBy(array('videocategory' => $vc->getId()));
            $homeVideo = $homeVideo->getVideo();

            $response = array('videos' => array());

            switch($block){
                case 'followed':
                    $videos = $videoRepo->search(null, $user, self::LIMIT_VIDEO, $offset, $vc->getId(), true, null, null, null, 'default', null, $homeVideo);
                    $response['videos'] = $serializer->values($videos, 'home_video');
                    break;
                case 'popular':
                    $videos = $videoRepo->search(null, null, self::LIMIT_VIDEO, $offset, $vc->getId(), false, null, null, null, 'default', null, $homeVideo);
                    $response['videos'] = $serializer->values($videos, 'home_video');
                    break;
            }
        }

        return $this->jsonResponse($response);
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
                    'videos' => $serializer->values($videos, 'big'),
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

        $results = $userFeed->latestActivity(10, $filters, array('photo', 'video'), $maxDate, null, $user, true, 'big');


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

        $results = $userFeed->popular(10, array('photo', 'video'), $maxDate, null, $user, true, 'big');


        return $this->jsonResponse($results);
    }
    
    private function checkFacebookRequest()
    {
        $request = $this->getRequest();
        $fbrequest = $request->get('request_ids');
        if ($fbrequest) {
            $fbrequest = explode(',', $fbrequest);
            if (!is_array($fbrequest)) $fbrequest = array($fbrequest);
            $fb = $this->get('app.facebook');
            try {
                $requester = $fb->getRequestAuthor($fbrequest);
                if ($requester) {
                    $session = $this->get('session');
                    $session->set('registration.fbrequest', $fbrequest);
                    $inviteurl = $this->get('contact.importer')->inviteUrl($requester);
                    return $this->redirect($inviteurl);
                } else {
                    // set some flash
                    $this->get('session')->setFlash('error', 'Error procesando invitación');
                }
            } catch(\Exception $e) {
                // failed to get requester user, set some flash
                $this->get('session')->setFlash('error', 'Error procesando invitación');
            }
        }
        return false;
    }

}
