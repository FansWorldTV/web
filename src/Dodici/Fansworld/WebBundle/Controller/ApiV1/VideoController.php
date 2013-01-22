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
 * API controller - Video
 * V1
 * @Route("/api_v1")
 */
class VideoController extends BaseController
{
	/**
     * [signed if user_id given] Video - list
     * 
     * @Route("/video/list", name="api_v1_video_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> recommended: boolean (0|1), if true get only videos of possible interest to user
     * - <optional> user_id: int, required for recommended = 1, will also be used for privacy filtering
     * - <required if user_id given> user_token: string, user token
     * - <optional> highlight: boolean (0|1), if true get only highlighted videos, if false, get only non-highlighted videos
     * - <optional> category_id: filter by video category id
     * - <optional> extra_fields: comma-separated extra fields to return (see below)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'weight'|'createdAt' (default: weight)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * - [signature params if user_id given]
     * 
     * @return 
     * array (
     * 		array (
     * 			id: int,
     * 			title: string,
     * 			image: array(id: int, url: string),
     * 			highlight: boolean,
     * 			category_id: int,
     * 			
     * 			// extra fields
     * 			author: @see SecurityController::loginAction() - without token,
     * 			content: string,
     * 			createdAt: int (timestamp UTC),
     * 			duration: int (seconds),
     * 			visitCount: int,
     * 			likeCount: int,
     * 			commentCount: int
     * 			
     * 		),
     * 		...
     * 		)
     * 
     */
    public function listAction()
    {
        try {
            $request = $this->getRequest();
                        
            $recommended = $request->get('recommended');
            $userid = $request->get('user_id');
            $highlight = $request->get('highlight');
            $categoryid = $request->get('category_id');
            
            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }
            
            $allowedfields = array('author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount');
            $extrafields = $this->getExtraFields($allowedfields);
            
            $pagination = $this->pagination(array('weight', 'createdAt'), 'weight');
            $sortcriteria = $pagination['sort'];
            if ($sortcriteria == 'weight') $sortcriteria = 'default';
            if ($sortcriteria == 'createdAt') $sortcriteria = 'date';
            
            $videos = $this->getRepository('Video')->search(
                null,
                $user,
                $pagination['limit'],
                $pagination['offset'],
                $categoryid,
                $highlight,
                null,
                null,
                null,
                $sortcriteria,
                null,
                null,
                null,
                $recommended,
                $pagination['sort_order']
            );
            
            $return = array();
            
            foreach ($videos as $video) {
                $return[] = $this->videoValues($video, $extrafields);
            }
            
            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * Video - show
     * 
     * @Route("/video/{id}", name="api_v1_video_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
	 * - <optional> extra_fields: comma-separated extra fields to return (see below)
	 * - <optional> imageformat: string
     * 
     * @return 
     * array (
     * 			id: int,
     * 			title: string,
     * 			image: array(id: int, url: string),
     * 			highlight: boolean,
     * 			category_id: int,
     * 			
     * 			// extra fields
     * 			author: @see SecurityController::loginAction() - without token,
     * 			content: string,
     * 			createdAt: int (timestamp UTC),
     * 			duration: int (seconds),
     * 			visitCount: int,
     * 			likeCount: int,
     * 			commentCount: int,
     * 
     * 			// extra fields, tagged entities
     * 			tagged_idols: array (
     * 				id: int,
     * 				firstname: string,
     * 				lastname: string,
     * 				image: array(id: int, url: string),
     * 			),
     * 			tagged_teams: array (
     * 				id: int,
     * 				title: string,
     * 				image: array(id: int, url: string),
     * 			),
     * 			tagged_tags: array (
     * 				id: int,
     * 				title: string
     * 			),
     * 			tagged_users: array (
     * 				id: int,
     *				username: string,
     *				email: string,
     *				firstname: string,
     *				lastname: string,
     *				image: array(id: int, url: string),
     * 			)
     * 		)
     * 
     */
    public function showAction($id)
    {
        try {
            $video = $this->getRepository('Video')->find($id);
            if (!$video) throw new HttpException(404, 'Video not found');
            
            $return = array(
                'id' => $video->getId(),
                'title' => (string)$video,
                'image' => $this->imageValues($video->getImage()),
                'highlight' => $video->getHighlight(),
                'category_id' => $video->getVideocategory()->getId()
            );
            
            $allowedfields = array(
            	'author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount',
                'tagged_idols', 'tagged_teams', 'tagged_tags', 'tagged_users'
            );
            $extrafields = $this->getExtraFields($allowedfields);
            
            foreach ($extrafields as $x) {
                switch ($x) {
                    case 'author':
                        $return['author'] = $video->getAuthor() ? $this->userArray($video->getAuthor()) : null;
                        break;
                    case 'createdAt':
                        $return['createdAt'] = (int)$video->getCreatedAt()->format('U');
                        break;
                    case 'tagged_idols':
                        $has = $video->getHasidols();
                        $t = array();
                        foreach ($has as $h) {
                            $ent = $h->getIdol();
                            $t[] = array(
                                'id' => $ent->getId(),
                                'firstname' => $ent->getFirstname(),
                                'lastname' => $ent->getLastname(),
                                'image' => $this->imageValues($ent->getImage())
                            );
                        }
                        $return[$x] = $t;
                        break;
                    case 'tagged_teams':
                        $has = $video->getHasteams();
                        $t = array();
                        foreach ($has as $h) {
                            $ent = $h->getTeam();
                            $t[] = array(
                                'id' => $ent->getId(),
                                'title' => (string)$ent,
                                'image' => $this->imageValues($ent->getImage())
                            );
                        }
                        $return[$x] = $t;
                        break;
                    case 'tagged_tags':
                        $has = $video->getHastags();
                        $t = array();
                        foreach ($has as $h) {
                            $ent = $h->getTag();
                            $t[] = array(
                                'id' => $ent->getId(),
                                'title' => (string)$ent
                            );
                        }
                        $return[$x] = $t;
                        break;
                    case 'tagged_users':
                        $has = $video->getHasusers();
                        $t = array();
                        foreach ($has as $h) {
                            $ent = $h->getTarget();
                            $t[] = $this->userArray($ent);
                        }
                        $return[$x] = $t;
                        break;
                    default:
                        $methodname = 'get'.ucfirst($x);
                        $return[$x] = $video->$methodname();
                        break;
                }
            }
            
            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * Video - streams
     * 
     * @Route("/video/{id}/streams", name="api_v1_video_streams", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params: none
     * 
     * @return 
     * array (
     * 			provider: 'youtube'|'vimeo'|'kaltura',
     * 			streams:
	 *              - youtube/vimeo id string, or
	 *              - array(
     * 					url: string (stream url),
     *                  format: array(
     *                      id: stream format id,
     *                      name: string (stream format's name),
     *                      description: string (stream format's description)
     *                  ),
     *                  bitrate: int (kb),
     *                  size: int (file size in kb),
     *                  width: int (px),
     *                  height: int (px)
     *				)
     * 		)
     * 
     */
    public function streamsAction($id)
    {
        try {
            $video = $this->getRepository('Video')->find($id);
            if (!$video) throw new HttpException(404, 'Video not found');
            
            if ($video->getYoutube()) {
                $return = array(
                    'provider' => 'youtube',
                    'streams' => $video->getYoutube()
                );
            } elseif ($video->getVimeo()) {
                $return = array(
                    'provider' => 'vimeo',
                    'streams' => $video->getVimeo()
                );
            } else {
                $return = array(
                    'provider' => 'kaltura',
                    'streams' => $this->get('kaltura')->streams($video->getStream())
                );
            }
            
            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
    /**
     * Video - categories list
     * 
     * @Route("/video/categories", name="api_v1_video_category_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'title' (default: title)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * 
     * @return 
     * array (
     * 		array (
     * 			id: int,
     * 			title: string
     * 		),
     * 		...
     * 		)
     * 
     */
    public function categoriesAction()
    {
        try {
            $pagination = $this->pagination(array('title'), 'title', 'ASC');
            
            $categories = $this->getRepository('VideoCategory')->findBy(
                array(),
                array($pagination['sort'] => $pagination['sort_order']),
                $pagination['limit'],
                $pagination['offset']
            );
            
            $return = array();
            
            foreach ($categories as $category) {
                $return[] = array(
                    'id' => $category->getId(),
                    'title' => $category->getTitle()
                );
            }
            
            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * Video - category detail
     * 
     * @Route("/video/categories/{id}", name="api_v1_video_category_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params: none
     * 
     * @return 
     * array (
     *     id: int,
     *     title: string
     * )
     * 
     */
    public function categoryshowAction($id)
    {
        try {
            $category = $this->getRepository('VideoCategory')->find($id);
            
            if (!$category) throw new HttpException(404, 'Video category not found');
            
            $return = array(
                'id' => $category->getId(),
                'title' => $category->getTitle()
            );
            
            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
