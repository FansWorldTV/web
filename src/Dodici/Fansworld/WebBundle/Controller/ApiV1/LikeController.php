<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

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
 * API controller - Like
 * V1
 * @Route("/api_v1")
 */
class LikeController extends BaseController
{
	/**
     * [signed] Add / remove like
     * 
     * @Route("/{entitytype}/{id}/like/{action}", name="api_v1_entity_like", requirements = {"entitytype" = "video|photo|comment", "id" = "\d+", "action" = "add|remove"})
     * @Method({"POST"})
     *
     * Post params:
     * - user_id: int
     * - [user token]
     * - [signature params]
     * 
     * @return
     * array (
     * 		like: boolean (true if you now like, false if you no longer like),
     * 		likecount: int (new likecount)
     * )
     */
    public function modifyAction($entitytype, $id)
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $type = ucfirst($entitytype);
                $entity = $this->getRepository($type)->find($id);
                if (!$entity) throw new HttpException(404, $type . ' not found');
                
                $liker = $this->get('liker');
                $likecount = $entity->getLikeCount();
                if ($liker->isLiking($entity)) {
                	$liker->unlike($entity, $user);
                	$likecount--;
                	$liked = false;
                } else {
                	$liker->like($entity, $user);
                	$likecount++;
                	$liked = true;
                }
                
                return $this->result(array('like' => $liked, 'likecount' => $likecount));
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
}
