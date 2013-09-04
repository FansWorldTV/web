<?php

namespace Dodici\Fansworld\WebBundle\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Application\Sonata\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Serializer\Serializer;

/**
 * Ajax Controller
 */
class AjaxController extends SiteController
{
    const FW_VIDEO_LIST_LIMIT = 30;
    const LIMIT_VIDEO = 12;
    const LIMIT_TAGS = 5;

    /**
     * Ajax list videos from fansworld
     * @Route("ajax/list/fansworld", name="home_ajaxfwlist")
     */
    public function ajaxFwVideoListAction()
    {
        $request = $this->getRequest();
        $serializer = $this->get('serializer');
        $page = $request->get('page', 1);
        $offset = ($page - 1) * self::FW_VIDEO_LIST_LIMIT;
        $fwVideos = $this->getRepository('Video')->findBy(array('highlight' => true, 'active' => true), array('createdAt' => 'desc', 'weight' => 'DESC'), self::FW_VIDEO_LIST_LIMIT, $offset);
        $countVideos = $this->getRepository('Video')->countBy(array('highlight' => true, 'active' => true));
        $addMore = $countVideos > ($page * self::FW_VIDEO_LIST_LIMIT) ? true : false;
        return $this->jsonResponse(array(
            'videos' => $serializer->values($fwVideos, 'home_video'),
            'addMore' => $addMore
        ));
    }

    /**
     * Ajax read all notifications for user
     *  @Route("/ajax/read-all-notification", name="ajax_readallnotification")
     */
    public function ajaxReadAllNotification()
    {
        $user = $this->getUser();
        $response = false;
        if ($user) {
            $notifications = $this->getRepository('Notification')->findBy(array('target' => $user->getId(), 'readed' => false, 'active' => true));
            if (count($notifications) > 0) {
                $em = $this->getDoctrine()->getEntityManager();
                foreach ($notifications as $notification) {
                    $notification->setReaded(true);
                    $em->persist($notification);
                    $em->flush();
                }
            }   
        }
        $response = true;
        return $this->jsonResponse($response);
    }

    /**
     *  Ajax get youtube data from url
     *  @Route("/ajax/get-youtube-data", name="ajax_getyoutubedata")
     */
    public function ajaxGetYoutubeData()
    {
        $request = $this->getRequest();
        $url = $request->get('url');
        $isValid = $this->get('video.uploader')->isValidYoutubeUrl($url);
        $validUrl = false;
        $metaData = "";

        if ($isValid) {
            $metaData = $this->get('video.uploader')->getYoutubeMeta($isValid);
            $validUrl = true;
        }

        $response =  array(
            'metadata'   => $metaData,
            'validurl' => $validUrl
        );

        return $this->jsonResponse($response);
    }

    /**
     * Ajax new home filter
     * @Route("ajax/homefilter", name="ajax_newhomefilter")
     */
    public function ajaxFilterAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $serializer = $this->get('serializer');
        $videoRepo = $this->getRepository('Video');
        $tagger = $this->get('tagger');

        if(!($user instanceof User)) $user = null;
        $vc = $request->get('vc', null);
        $genre = $request->get('genre', null);
        $page = 1;
        $offset = ($page - 1) * self::LIMIT_VIDEO;

        $response = array('highlighted' => array(), 'follow' => array(), 'popular' => array());

        $limitWithTheHighlighted = (self::LIMIT_VIDEO - 3);
        $videos = $videoRepo->highlight($genre, $vc , null, $limitWithTheHighlighted, 0);
        $response['highlighted'] = $serializer->values($videos, 'home_video');

        if ($user instanceof User) {
            $videos = $videoRepo->follow($user, $genre, $vc, self::LIMIT_VIDEO, $offset);
            $response['follow'] = $serializer->values($videos, 'home_video');
            // $response['follow']['tags'] = $tagger->trendingInRecommended($user, self::LIMIT_TAGS, 0);
            $videosCount = $videoRepo->countSearch(null, $user, $vc, false, null, null, null, null, null, null, null, true, $genre);
            $response['follow']['addMore'] = $videosCount > (($page) * self::LIMIT_VIDEO) ? true : false;
        }
  
        $videos = $videoRepo->popular($genre, $vc, null, self::LIMIT_VIDEO, $offset);
        $response['popular'] = $serializer->values($videos, 'home_video');
        //$response['popular']['tags'] = $tagger->usedInVideos('popular', $vc, $genre, self::LIMIT_TAGS, 0);
        $videosCount = $videoRepo->countSearch(null, null, $vc, false, null, null, null, null, null, null, null, null, $genre);
        $response['popular']['addMore'] = $videosCount > (($page) * self::LIMIT_VIDEO) ? true : false;

        return $this->jsonResponse($response);
    }



}