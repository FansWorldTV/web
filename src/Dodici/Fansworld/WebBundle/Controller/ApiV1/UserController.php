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
     * @Route("/user/fans", name="api_v1_user_fans")
     * @Method({"GET"})
     *
     * Get params:
	 * - target_id: int
	 * - <optional> direction: 'followed'|'followers' (filter by users I follow, or users that follow me)
	 * - <optional> filter: see below
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> imageformat: string
     * - [signature params]
     *
     * filters:
     * - 'nearby': same country/city
     * - 'favoriteteam': same favorite team(s)
     * - 'mostsimilar': at least one common idol/team, order by most coincidences
     *
     * @return
     * array (
     * 		@see self::showAction(),
     * 		...
     * )
     */
    public function listAction()
    {
        try {
                $request = $this->getRequest();

                $targetid = $request->get('target_id');
                if (!$targetid) throw new HttpException(400, 'Invalid target_id');
                $target = $this->getRepository('User')->find($targetid);
                if (!($target instanceof User)) throw new HttpException(404, 'Target user not found');

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
                    $target,
                    $direction,
                    $pagination['limit'],
                    $pagination['offset']
                );

                $return = array();
                foreach ($users as $u) {
                    $return[] = $this->userArray($u);
                }

                return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

    /**
     * [signed] User - fan/unfan
     *
     * @Route("/user/fan/{action}", name="api_v1_user_fan", requirements = {"action" = "add|remove"})
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
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $targetids = $request->get('target_id');
                if (!$targetids) throw new HttpException(400, 'Requires target_id');
                if (!is_array($targetids)) $targetids = array($targetids);
                if (array_unique($targetids) !== $targetids) throw new HttpException(400, 'Duplicate target_id');
                
                $updates = array();
                
                foreach ($targetids as $targetid) {
                    $target = $this->getRepository('User')->find($targetid);
                    if (!$target) throw new HttpException(404, 'Target user not found');
    
                    if ($action == 'add') {
                        $this->get('friender')->friend($target, null, $user);
                    } elseif ($action == 'remove') {
                        $this->get('friender')->remove($target, $user);
                    } else {
                        throw new HttpException(400, 'Invalid fan action');
                    }
                    
                    $updates[] = $target;
                }

                $result = array();
                foreach ($updates as $ui) $result[] = array('id' => $ui->getId(), 'fanCount' => $ui->getFanCount()); 

                return $this->result((count($result) == 1) ? $result[0] : $result);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

	/**
     * [signed] User show
     *
     * @Route("/user/{id}", name="api_v1_user_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
	 * - <optional> [user_token]
     * - [signature params]
     *
     * @return
     * array (
     * 		id: int,
     * 		username: string,
     * 		email: string,
     * 		firstname: string,
     * 		lastname: string,
     * 		image: array(id: int, url: string),
     *      splash: array(id: int, url: string),
     *      fanCount: int,
     *      idolFollowCount: int,
     *      teamFollowCount: int,
     *      fanFollowCount: int
     * )
     */
    public function showAction($id)
    {
        try {
                $request = $this->getRequest();
                if (!$id) throw new HttpException(400, 'Invalid user_id');

                if ($request->get('user_token')) {
                    $user = $this->checkUserToken($id, $request->get('user_token'));
                    $hastoken = true;
                } else {
                    $user = $this->getRepository('User')->findOneBy(array('id' => $id, 'enabled' => true));
                    if (!$user) throw new HttpException(404, 'User not found');
                    $hastoken = false;
                }

                return $this->result($this->userArray($user));
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }


    /**
     * [signed] User Teams
     *
     * @Route("/user/{id}/teams", name="api_v1_user_teams", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - target_id: int
     * - [user_token]
     * - <optional> user_id: int
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> imageformat: string
     * - [signature params]
     *
     * @return
     * array (Serializer of team)
     *
     */
    public function teamsListAction($id)
    {
        try {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $targetid = $id;

                if (!$targetid) throw new HttpException(400, 'Invalid target_id');
                $target = $this->getRepository('User')->find($targetid);

                if (!($target instanceof User)) throw new HttpException(404, 'Target user not found');

                if ($userid) {
                    $user = $this->checkUserToken($userid, $request->get('user_token'));
                    if (!($user instanceof User)) throw new HttpException(404, 'User not found');
                }

                $pagination = $this->pagination();
                $pagination['sort_order'] = null;
                $pagination['sort'] = null;

                $imageformat = $request->get('imageformat');
                if (null == $imageformat) $imageformat = 'small';

                $teamships = $this->getRepository('Teamship')->byUser(
                    $target,
                    $pagination['limit'],
                    $pagination['offset']);

                $return = array();
                foreach ($teamships as $teamship) {
                    $return[] = $this->get('serializer')->values($teamship->getTeam(), $imageformat);
                }

                if ($userid) {
                    $teamships = $this->getRepository('Teamship')->byUser($user);
                    $teamIds = array();

                    foreach ($teamships as $teamship) {
                        $teamIds[] = $teamship->getTeam()->getId();
                    }

                    foreach ($return as &$rta) {
                        if ($userid != $targetid) {
                            in_array($rta['id'], $teamIds) ? $rta['followed'] = true : $rta['followed'] = false;
                        } else {
                            $rta['followed'] = true;
                        }
                    }
                }

                return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

     /**
     * [signed] User Idols
     *
     * @Route("/user/{id}/idols", name="api_v1_user_idols", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - [user_token]
     * - <optional> user_id: int
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> imageformat: string
     * - [signature params]
     *
     * @return
     * array (Serializer of idol)
     *
     */
    public function idolsListAction($id)
    {
        try {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $targetid = $id;

                if (!$targetid) throw new HttpException(400, 'Invalid target id');
                $target = $this->getRepository('User')->find($targetid);

                if (!($target instanceof User)) throw new HttpException(404, 'Target user not found');

                if ($userid) {
                    $user = $this->checkUserToken($userid, $request->get('user_token'));
                    if (!($user instanceof User)) throw new HttpException(404, 'User not found');
                }

                $pagination = $this->pagination();
                $pagination['sort_order'] = null;
                $pagination['sort'] = null;

                $imageformat = $request->get('imageformat');
                if (null == $imageformat) $imageformat = 'small';

                $idolships = $this->getRepository('Idolship')->byUser(
                    $target,
                    $pagination['limit'],
                    $pagination['offset']);

                $return = array();
                foreach ($idolships as $idolship) {
                    $return[] = $this->get('serializer')->values($idolship->getIdol(), $imageformat);
                }

                 if ($userid) {
                    $idolships = $this->getRepository('Idolship')->byUser($user);
                    $idolIds = array();

                    foreach ($idolships as $idolship) {
                        $idolIds[] = $idolship->getIdol()->getId();
                    }

                    foreach ($return as &$rta) {
                        if ($userid != $targetid) {
                            in_array($rta['id'], $idolIds) ? $rta['followed'] = true : $rta['followed'] = false;
                        } else {
                            $rta['followed'] = true;
                        }
                    }
                }

                return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }


    /**
     * [signed] User fans
     *
     * @Route("/user/{id}/fans", name="api_v1_user_fans", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - [user_token]
     * - <optional> user_id: int
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> imageformat: string
     * - [signature params]
     *
     * @return
     * array (Serializer of user) + followed(boolean) if user_id is defined
     *
     */
    public function userFansListAction($id)
    {
        try {
                $request = $this->getRequest();
                $userid = $request->get('user_id');

                if (!$id) throw new HttpException(400, 'Invalid user id');
                $user = $this->getRepository('User')->find($id);
                if (!$user) throw new HttpException(404, "User not found");

                if ($userid) {
                    $userByGet = $this->checkUserToken($userid, $request->get('user_token'));
                    if (!($userByGet instanceof User)) throw new HttpException(404, 'User by get not found');
                }

                $pagination = $this->pagination();
                $pagination['sort_order'] = null;
                $pagination['sort'] = null;

                $imageformat = $request->get('imageformat');
                if (null == $imageformat) $imageformat = 'small';

                $fansOfUser = $this->getRepository('User')->FriendUsers($user, null, $pagination['limit'], $pagination['offset'], 'score');

                $response = array();
                foreach ($fansOfUser as $aFan) {
                    if($aFan->getId() != $userid) {
                        $response[] = $this->userArray($aFan);
                    }
                }

                if ($userid) {
                    foreach ($response as &$rta) {
                        $friendShip = $this->getRepository('Friendship')->findOneBy(array('author' => $userid, 'target'=> $rta['id'], 'active' => true));
                        $friendShip ? $rta['followed'] = true : $rta['followed'] = false;
                    }
                }

                return $this->result($response, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }

    }
}