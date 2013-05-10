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
     * - <optional> user_id: int, required for recommended = 1, watchlisted extra field, will also be used for privacy filtering
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
     * 			provider: kaltura|youtube,
     *
     * 			// extra fields
     * 			author: @see SecurityController::loginAction() - without token,
     * 			content: string,
     * 			createdAt: int (timestamp UTC),
     * 			duration: int (seconds),
     * 			visitCount: int,
     * 			likeCount: int,
     * 			commentCount: int,
     * 			watchlisted: boolean,
     * 			url: string,
     * 			liked: boolean
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

            $allowedfields = array('author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount', 'watchlisted', 'url', 'liked');
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
                $return[] = $this->videoValues($video, $extrafields, $user);
            }

            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

    /**
     * [signed] Video - delete (active/inactive)
     *
     * @Route("/video/delete", name="api_v1_video_delete")
     * @Method({"POST"})
     *
     * Post params:
     * - user_id: int
     * - video_id: int|array
     * - [user_token]
     * - [signature params]
     *
     */
    public function deleteAction() {

        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $this->checkUserToken($userid, $request->get('user_token'));

                $videoids = $request->get('video_id');
                if (!is_array($videoids)) $videoids = array($videoids);
                if (array_unique($videoids) !== $videoids) throw new HttpException(400, 'Duplicate video_id');

                $em = $this->getDoctrine()->getEntityManager();

                foreach ($videoids as $videoid) {
                    $video = $this->getRepository('video')->find($videoid);
                    if (!$video) throw new HttpException(404, 'video not found - id: ' . $videoid);

                    $video->setActive(false);

                    $em->persist($video);
                }

                $em->flush();

                return $this->result(true);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

	/**
     * [signed if user_id given] Video - show
     *
     * @Route("/video/{id}", name="api_v1_video_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
	 * - <optional> user_id: int, required for watchlisted extra field, also privacy checking
	 * - <optional> extra_fields: comma-separated extra fields to return (see below)
	 * - <optional> imageformat: string
     * - [signature params if user_id given]
     *
     * @return
     * array (
     * 			id: int,
     * 			title: string,
     * 			image: array(id: int, url: string),
     * 			highlight: boolean,
     * 			category_id: int,
     * 			provider: kaltura|youtube,
     *
     * 			// extra fields
     * 			author: @see SecurityController::loginAction() - without token,
     * 			content: string,
     * 			createdAt: int (timestamp UTC),
     * 			duration: int (seconds),
     * 			visitCount: int,
     * 			likeCount: int,
     * 			commentCount: int,
     * 			watchlisted: boolean,
     * 			url: string,
     * 			liked: boolean,
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
                'category_id' => $video->getVideocategory()->getId(),
                'provider' => ($video->getYoutube() ? 'youtube' : 'kaltura')
            );

            $request = $this->getRequest();
            $userid = $request->get('user_id');
            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }


            $allowedfields = array(
            	'author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount', 'watchlisted', 'url', 'liked',
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
                    case 'watchlisted':
                        if ($user) $return[$x] = $this->get('video.playlist')->isInPlaylist($video, $user);
                        break;
                    case 'liked':
                        if ($user) $return[$x] = ($this->get('liker')->isLiking($video, $user) ? true : false);
                        break;
                    case 'url':
                        $return[$x] = $this->get('router')->generate('video_show', array('id' => $video->getId(), 'slug' => $video->getSlug()), true);
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
     * @Route("/video/{id}/streams/{protocol}", name="api_v1_video_streams", requirements = {"id" = "\d+"}, defaults = {"protocol" = "progressive"})
     * @Method({"GET"})
     *
     * Get params: none
     * Url params:
     * - protocol: 'progressive'(default)|'rtmp'|'hls'
     * 
     * @return 
     * array (
     * 			provider: 'youtube'|'vimeo'|'kaltura',
     * 			streams:
	 *              - youtube/vimeo id string, or
	 *              - (hls/rtmp) url string, or
	 *              - (progressive) array(
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
    public function streamsAction($id, $protocol)
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
                    'streams' => $this->get('kaltura')->streams($video->getStream(), $protocol)
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


    /**
     * [signed if user_id given] Video - list
     *
     * @Route("/{entityType}/{id}/videos", name="api_v1_video_list_by_entity", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> recommended: boolean (0|1), if true get only videos of possible interest to user
     * - <optional> user_id: int, required for recommended = 1, watchlisted extra field, will also be used for privacy filtering
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
     *      array (
     *          id: int,
     *          title: string,
     *          image: array(id: int, url: string),
     *          highlight: boolean,
     *          category_id: int,
     *          provider: kaltura|youtube,
     *
     *          // extra fields
     *          author: @see SecurityController::loginAction() - without token,
     *          content: string,
     *          createdAt: int (timestamp UTC),
     *          duration: int (seconds),
     *          visitCount: int,
     *          likeCount: int,
     *          commentCount: int,
     *          watchlisted: boolean,
     *          url: string,
     *          liked: boolean
     *      ),
     *      ...
     *      )
     *
     */
    public function listByEntityAction($entityType, $id)
    {
        try {
            $request = $this->getRequest();

            $allowedTypes = array('user', 'team', 'idol');
            if (!in_array($entityType, $allowedTypes)) throw new HttpException(400, 'Invalid entity type');
            if (!$id) throw new HttpException(400, 'Invalid id');
            $entity = $this->getRepository(ucfirst($entityType))->findOneBy(array('id' => $id));
            if (is_null($entity)) throw new HttpException(400, $entityType.' not found');

            $recommended = $request->get('recommended');
            $userid = $request->get('user_id');
            $highlight = $request->get('highlight');
            $categoryid = $request->get('category_id');

            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }

            $allowedfields = array('author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount', 'watchlisted', 'url', 'liked');
            $extrafields = $this->getExtraFields($allowedfields);

            $pagination = $this->pagination(array('weight', 'createdAt'), 'weight');
            $sortcriteria = $pagination['sort'];
            if ($sortcriteria == 'weight') $sortcriteria = 'default';
            if ($sortcriteria == 'createdAt') $sortcriteria = 'date';

            $videos = $this->getRepository('Video')->search(
                $entity,
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
                $return[] = $this->videoValues($video, $extrafields, $user);
            }

            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
