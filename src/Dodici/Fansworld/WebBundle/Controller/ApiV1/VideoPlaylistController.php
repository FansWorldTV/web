<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\ApiV1\BaseController;

/**
 * API controller - Video Playlist
 * V1
 * @Route("/api_v1")
 */
class VideoPlaylistController extends BaseController
{
	/**
     * [signed] List
     * 
     * @Route("/video/playlist/list", name="api_v1_video_playlist_list")
     * @Method({"GET"})
     *
     * Get params:
     * - user_id: int
     * - [user token]
     * - <optional> extra_fields: comma-separated extra fields to return (see VideoController::listAction())
	 * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'createdAt' (default: createdAt)
     * - <optional> sort_order: 'asc'|'desc' (default: asc)
     * - [signature params]
     * 
     * @return 
     * @see VideoController::listAction()
     */
    public function listAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $pagination = $this->pagination(array('createdAt'), 'createdAt', 'ASC');
                
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $wls = $this->get('video.playlist')->get(
                    $user,
                    $pagination['limit'],
                    $pagination['offset'],
                    array($pagination['sort'] => $pagination['sort_order'])
                );
                
                $allowedfields = array('author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount', 'watchlisted', 'url', 'liked');
                $extrafields = $this->getExtraFields($allowedfields);
                
                $return = array();
                foreach ($wls as $wl) $return[] = $this->videoValues($wl->getVideo(), $extrafields, $user);
                
                return $this->result($return, $pagination);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] Add/remove
     * 
     * @Route("/video/playlist/{action}", name="api_v1_video_playlist_modify", requirements = {"action" = "add|remove"})
     * @Method({"POST"})
     *
     * Get params:
     * - user_id: int
     * - [user token]
     * - video_id: int
     * - [signature params]
     */
    public function modifyAction($action)
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $videoid = $request->get('video_id');
                if (!$videoid) throw new HttpException(400, 'Invalid video_id');
                $video = $this->getRepository('Video')->find($videoid);
                if (!$video) throw new HttpException(404, 'Video not found');
                
                if ($action == 'add') {
                    $this->get('video.playlist')->add($video, $user);
                } elseif ($action == 'remove') {
                    $this->get('video.playlist')->remove($video, $user);
                }
                
                return $this->result(true);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
