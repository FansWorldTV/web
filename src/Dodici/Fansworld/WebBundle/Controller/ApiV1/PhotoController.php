<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Photo;
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
     * [signed] Photo - delete (active/inactive)
     * 
     * @Route("/photo/delete", name="api_v1_photo_delete")
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
                $user = $this->getRepository('User')->find($userid); //$this->checkUserToken($userid, $request->get('user_token'));
                
                $photoids = $request->get('photo_id');
                if (!is_array($photoids)) $photoids = array($photoids);
                if (array_unique($photoids) !== $photoids) throw new HttpException(400, 'Duplicate idol_id');
                
                foreach ($photoids as $photoid) {
                    $photo = $this->getRepository('Photo')->find($photoid);
                    if (!$photo) throw new HttpException(404, 'Photo not found - id: ' . $photoid);
                    
                    $photo->setActive(false);
                }

                $this->getDoctrine()->getEntityManager()->flush();
                
                return $this->result(true);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
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
            $photo = $this->getRepository('Photo')->find($id);
            if (!$photo) throw new HttpException(404, 'Photo not found');
                        
            $request = $this->getRequest();
            $userid = $request->get('user_id');
            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }
            
            $allowedfields = array(
            	'author', 'album', 'content', 'createdAt', 'visitCount', 'likeCount', 'commentCount', 'url', 'liked',
                'tagged_idols', 'tagged_teams', 'tagged_tags', 'tagged_users'
            );
            $extrafields = $this->getExtraFields($allowedfields);
            
            $return = $this->photoValues($photo, $extrafields, $user);
            
            foreach ($extrafields as $x) {
                switch ($x) {
                    case 'tagged_idols':
                        $has = $photo->getHasidols();
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
                        $has = $photo->getHasteams();
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
                        $has = $photo->getHastags();
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
                        $has = $photo->getHasusers();
                        $t = array();
                        foreach ($has as $h) {
                            $ent = $h->getTarget();
                            $t[] = $this->userArray($ent);
                        }
                        $return[$x] = $t;
                        break;
                    default:
                        break;
                }
            }
            
            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
    private function photoValues(Photo $photo, $extrafields = array(), $user = null)
    {
        $rv = array(
            'id' => $photo->getId(),
            'title' => (string)$photo,
            'image' => $this->imageValues($photo->getImage())
        );
        
        foreach ($extrafields as $x) {
            switch ($x) {
                case 'author':
                    $rv['author'] = $photo->getAuthor() ? $this->userArray($photo->getAuthor()) : null;
                    break;
                case 'album':
                    if ($photo->getAlbum()) {
                        $album = $photo->getAlbum();
                        $rv['album'] = array('id' => $album->getId(), 'title' => (string)$album, 'photoCount' => $album->getPhotoCount());
                    }
                    else $rv['album'] = null;
                    break;
                case 'createdAt':
                    $rv['createdAt'] = (int)$photo->getCreatedAt()->format('U');
                    break;
                case 'liked':
                    if ($user) $rv[$x] = $this->get('liker')->isLiking($photo, $user) ? true : false;
                    break;
                case 'url':
                    $rv[$x] = $this->get('router')->generate('photo_show', array('id' => $photo->getId(), 'slug' => $photo->getSlug()), true);
                    break;
                default:
                    $methodname = 'get'.ucfirst($x);
                    if (method_exists($photo, $methodname)) {
                        $rv[$x] = $photo->$methodname();
                    }
                    break;
            }
        }
        
        return $rv;
    }
    
}