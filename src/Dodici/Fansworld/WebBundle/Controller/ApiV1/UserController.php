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
 * API controller - User
 * V1
 * @Route("/api_v1")
 */
class UserController extends BaseController
{
	/**
     * [signed] User Fans
     * 
     * @Route("/user/fans", name="api_user_fans")
     * @Method({"GET"})
     *
     * Get params:
	 * - user_id: int
	 * - [user_token]
	 * - <optional> direction: 'followed'|'followers' (filter by users I follow, or users that follow me)
	 * - <optional> filter: see below
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - [signature params]
     * 
     * filters:
     * - 'nearby': same country/city
     * - 'favoriteteam': same favorite team(s)
     * - 'mostsimilar': at least one common idol/team, order by most coincidences
     * 
     * @return 
     * array (
     * 		array (
     * 			id: int,
     * 			firstname: string,
     * 			lastname: string,
     * 			image: string (url of image)
     * 			fanCount: int,
     * 			sex: string,
     * 			username: string,
     * 			url: string
     * 		),
     * 		...
     * )
     */
    public function listAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $pagination = $this->pagination();
                $pagination['sort_order'] = null;
                $pagination['sort'] = null;
                $direction = null;
                $dirstr = $request->get('direction');
                if ($dirstr == 'followed') $direction = true;
                if ($dirstr == 'followers') $direction = false;
                
                $filter = $request->get('filter');
                
                $methodname = 'fans';
                
                if ($filter) {
                    switch ($filter) {
                        case 'nearby': $methodname = 'fansNearby'; break;
                        case 'favoriteteam': $methodname = 'fansSameFavoriteTeam'; break;
                        case 'mostsimilar': $methodname = 'fansMostSimilar'; break;
                        default: throw new HttpException(400, 'Invalid filter'); break;
                    }
                }
                
                $users = $this->getRepository('User')->$methodname(
                    $user,
                    $direction,
                    $pagination['limit'],
                    $pagination['offset']
                );

                $return = $this->get('serializer')->values($users);
                
                return $this->result($return, $pagination);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
        
    /**
     * [signed] User - fan/unfan
     * 
     * @Route("/user/fan/{action}", name="api_user_fan", requirements = {"action" = "add|remove"})
     * @Method({"POST"})
     *
     * Post params:
	 * - user_id: int
	 * - target_id: int
	 * - [user_token]
     * - [signature params]
     * 
     */
    public function fanAction($action)
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $target = $this->getRepository('User')->find($request->get('target_id'));
                if (!$target) throw new HttpException(404, 'Target user not found');
                
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                if ($action == 'add') {
                    $this->get('friender')->friend($target, null, $user);
                } elseif ($action == 'remove') {
                    $this->get('friender')->remove($target, $user);
                } else {
                    throw new HttpException(400, 'Invalid fan action');
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
