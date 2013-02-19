<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Share;
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
 * API controller - Comments
 * V1
 * @Route("/api_v1")
 */
class CommentController extends BaseController
{
	/**
     * [signed if user_id given] List
     * 
     * @Route("/{entitytype}/{id}/comments", name="api_v1_entity_comments", requirements = {"entitytype" = "team|idol|video|photo|user|comment", "id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> user_id: int
     * - <required if user_id given> [user token]
     * - <optional> lastid: int (the oldest id to start retrieving from)
	 * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - [signature params if user_id given]
     * 
     * @return 
     * array(
     *     array(
     * 	    	id: int,
     * 	    	canDelete: boolean,
     * 	    	type: string,
     * 	    	content: string,
     * 	    	createdAt: int (ts UTC),
     * 	    	commentCount: int,
     * 	    	author: @see UserController:showAction,
     * 	    	share: array(
     * 	    		type: string (video|photo|album|event),
	 *	    		id: int, 
	 *	    		title: string, 
	 *	    		likecount: int, 
	 *	    		image: string (url)
     * 	    	)
     *     ),
     *     ...
     * )
     */
    public function listAction($entitytype, $id)
    {
        try {
            $request = $this->getRequest();
            $userid = $request->get('user_id');
            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }
            
            $lastid = $request->get('lastid');
            $pagination = $this->pagination();
            $pagination['sort_order'] = null;
            $pagination['sort'] = null;
            $pagination['lastid'] = $lastid;
            
            $entity = $this->getRepository(ucfirst($entitytype))->find($id);
            if (!$entity) throw new HttpException(404, ucfirst($entitytype) . ' not found');
            
            $comments = $this->getRepository('Comment')->wallEntity($entity, $user, $lastid, $pagination['limit'], $pagination['offset']);
            
            $return = array();
            foreach ($comments as $comment) {
                $return[] = $this->jsonComment($comment);
            }
            
            return $this->result($return, $pagination);
            
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
    /**
     * [signed] Add comment
     * 
     * @Route("/{entitytype}/{id}/comments/add", name="api_v1_entity_add_comment", requirements = {"entitytype" = "team|idol|video|photo|user|comment|event", "id" = "\d+"})
     * @Method({"POST"})
     *
     * Get params:
     * - user_id: int
     * - [user token]
     * - content: string
     * - <optional> privacy: int
     * - [signature params]
     */
    public function addAction($entitytype, $id)
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $entity = $this->getRepository(ucfirst($entitytype))->find($id);
                if (!$entity) throw new HttpException(404, ucfirst($entitytype) . ' not found');
                
                $privacy = $request->get('privacy', Privacy::EVERYONE);
                $content = $request->get('content');
                if (!$content) throw new HttpException(400, 'Invalid content');
                
                $team = null;
                if ($entitytype == 'event') {
                    $eventship = $this->getRepository('Eventship')->findOneBy(array('author' => $userid, 'event' => $id));
                    if (!$eventship) throw new HttpException(401, 'User has not checked into event');
                    $team = $eventship->getTeam();
                }
                
                $this->get('commenter')->comment($user, $entity, $content, $privacy, $team);
                
                return $this->result(true);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] Remove
     * 
     * @Route("/comment/{id}/remove", name="api_v1_comment_remove", requirements = {"id" = "\d+"})
     * @Method({"POST"})
     *
     * Get params:
     * - user_id: int
     * - [user token]
     * - [signature params]
     */
    public function removeAction($id)
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                if (!$id) throw new HttpException(400, 'Invalid comment_id');
                $comment = $this->getRepository('Comment')->find($id);
                if (!$comment) throw new HttpException(404, 'Comment not found');
                
                if ($this->get('appstate')->canDelete($comment, $user)) {
                    $em = $this->getDoctrine()->getEntityManager();
                    $em->remove($comment);
                    $em->flush();
                } else {
                    throw new HttpException(403, 'User cannot delete comment');
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
