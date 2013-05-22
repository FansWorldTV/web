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
 * API controller - Video Subscription
 * V1
 * @Route("/api_v1")
 */
class VideoSubscriptionController extends BaseController
{
	/**
     * [signed] List
     * 
     * @Route("/video/categories/subscription/list", name="api_v1_video_categories_subscription_list")
     * @Method({"GET"})
     *
     * Get params:
     * - user_id: int
     * - [user token]
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - [signature params]
     * 
     * @return 
     * @see VideoController::categoriesAction()
     */
    public function listAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $pagination = $this->pagination();
                $pagination['sort_order'] = null;
                $pagination['sort'] = null;
                
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $wls = $this->get('subscriptions')->get(
                    $user,
                    $pagination['limit'],
                    $pagination['offset']
                );
                
                $return = array();
                foreach ($wls as $wl) $return[] = $wl->getVideoCategory();
                
                return $this->result($this->get('serializer')->values($return, $this->getImageFormat(), $this->getImageFormat('splash'), 'object'), $pagination);
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
     * @Route("/video/categories/subscription/{action}", name="api_v1_video_categories_subscription_modify", requirements = {"action" = "add|remove"})
     * @Method({"POST"})
     *
     * Get params:
     * - user_id: int
     * - [user token]
     * - videocategory_id: int
     * - [signature params]
     */
    public function modifyAction($action)
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $vcid = $request->get('videocategory_id');
                if (!$vcid) throw new HttpException(400, 'Invalid videocategory_id');
                $vc = $this->getRepository('VideoCategory')->find($vcid);
                if (!$vc) throw new HttpException(404, 'Videocategory not found');
                
                if ($action == 'add') {
                    $done = $this->get('subscriptions')->subscribe($vc, $user);
                    if (!$done) throw new HttpException(400, 'User already subscribed');
                } elseif ($action == 'remove') {
                    $done = $this->get('subscriptions')->unsubscribe($vc, $user);
                    if (!$done) throw new HttpException(400, 'User is not subscribed');
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
