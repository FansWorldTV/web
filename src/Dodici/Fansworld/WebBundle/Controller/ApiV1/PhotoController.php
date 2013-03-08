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
 * API controller - Photo
 * V1
 * @Route("/api_v1")
 */
class PhotoController extends BaseController
{
	/**
     * [signed if user_id given] Photo - list
     * 
     * @Route("/photo/list", name="api_v1_photo_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> user_id: int, the user requesting the list, will be used for privacy filtering
     * - <required if user_id given> user_token: string, user token
     * - <optional> album_id: filter by album id
     * - <optional> author_id: filter by author user id
     * - <optional> extra_fields: comma-separated extra fields to return (see below)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'weight'|'createdAt' (default: weight)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * - <optional> imageformat: string
     * - [signature params if user_id given]
     * 
     * @return 
     * array (
     * 		array (
     * 			id: int,
     * 			title: string,
     * 			image: array(id: int, url: string),
     * 			
     *			// extra fields
     * 			author: @see SecurityController::loginAction() - without token,
     * 			album: array (
     * 				id: int,
     * 				title: string,
     * 				photoCount: int
     * 			)
     * 			content: string,
     * 			createdAt: int (timestamp UTC),
     * 			visitCount: int,
     * 			likeCount: int,
     * 			commentCount: int,
     * 			url: string,
     * 			liked: boolean
     * 		),
     * 		...
     * 		)
     * )
     */
    public function listAction()
    {
        try {
            $request = $this->getRequest();
            
            $userid = $request->get('user_id');
            $albumid = $request->get('album_id');
            $authorid = $request->get('author_id');
            
            $album = null;
            $author = null;
            if ($albumid) {
                $album = $this->getRepository('Album')->find($albumid);
                if (!$album || !$album->getActive()) throw new HttpException(400, 'Invalid album_id');
            }
            if ($authorid) {
                $author = $this->getRepository('User')->find($authorid);
                if (!$author || !$author->getEnabled()) throw new HttpException(400, 'Invalid author_id');
            }
            
            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }
            
            $allowedfields = array('author', 'album', 'content', 'createdAt', 'visitCount', 'likeCount', 'commentCount', 'url', 'liked');
            $extrafields = $this->getExtraFields($allowedfields);
            
            $pagination = $this->pagination(array('weight', 'createdAt'), 'weight');
            $sortcriteria = $pagination['sort'];
            if ($sortcriteria == 'weight') $sortcriteria = 'default';
            if ($sortcriteria == 'createdAt') $sortcriteria = 'date';
            
            $photos = array();
            
            $return = array();
            
            foreach ($photos as $photo) {
                $return[] = $this->photoValues($photo, $extrafields, $user);
            }
            
            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed if user_id given] Photo - show
     * 
     * @Route("/photo/{id}", name="api_v1_photo_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> user_id: int, the user requesting the list, will be used for privacy filtering
     * - <required if user_id given> user_token: string, user token
	 * - <optional> extra_fields: comma-separated extra fields to return (see below)
	 * - <optional> imageformat: string
     * - [signature params if user_id given]
     * 
     * @return 
     * array (
     * 			id: int,
     * 			title: string,
     * 			image: array(id: int, url: string),
     * 			
     *			// extra fields
     * 			author: @see SecurityController::loginAction() - without token,
     * 			album: array (
     * 				id: int,
     * 				title: string,
     * 				photoCount: int
     * 			)
     * 			content: string,
     * 			createdAt: int (timestamp UTC),
     * 			visitCount: int,
     * 			likeCount: int,
     * 			commentCount: int,
     * 			url: string,
     * 			liked: boolean
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
    
}