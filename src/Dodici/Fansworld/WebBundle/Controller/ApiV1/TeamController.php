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
 * API controller - Team
 * V1
 * @Route("/api_v1")
 */
class TeamController extends BaseController
{
	/**
     * Team - list
     *
     * @Route("/team/list", name="api_v1_team_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> country: int (country id to filter by)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'fanCount'|'title' (default: fanCount)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * - <optional> imageformat: string
     *
     * @return
     * array (
     * 		array (
     * 			id: int,
     * 			title: string,
     * 			image: array(id: int, url: string),
     * 			fanCount: int
     * 		),
     * 		...
     * 		)
     * )
     */
    public function listAction()
    {
        try {
            $request = $this->getRequest();
            $countryid = $request->get('country');

            $filters = array('active' => true);

            if ($countryid) {
                $country = $this->getRepository('Country')->find($countryid);
                if (!$country) throw new HttpException(400, 'Invalid country');
                $filters['country'] = $country->getId();
            }

            $pagination = $this->pagination(array('fanCount', 'title'), 'fanCount');
            $sort = $pagination['sort'];

            if ($sort == 'title') $sort = 'shortname';

            $teams = $this->getRepository('Team')->findBy(
                $filters,
                array($sort => $pagination['sort_order']),
                $pagination['limit'],
                $pagination['offset']);

            $return = array();

            foreach ($teams as $team) {
                $return[] = array(
                    'id' => $team->getId(),
                    'title' => (string)$team,
                    'image' => $this->imageValues($team->getImage()),
                    'fanCount' => $team->getFanCount()
                );
            }

            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

	/**
     * Team - show
     *
     * @Route("/team/{id}", name="api_v1_team_show", requirements = {"id" = "\d+"})
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
     * 			fanCount: int
     *
     * 			// extra fields
     * 			content: string,
     * 			foundedAt: int (ts UTC),
     * 			splash: string (url of image),
     * 			categories: array(
     * 				array(
     * 					id: int,
     * 					title: string,
     * 					sport: int (id)
     * 				),
     * 				...
     * 			),
     * 			country: int (id),
     * 			twitter: string,
     * 			photoCount: int,
     * 			videoCount: int,
     * 			visitCount: int
     * 		)
     *
     */
    public function showAction($id)
    {
        try {
            $team = $this->getRepository('Team')->find($id);
            if (!$team) throw new HttpException(404, 'Team not found');

            $return = array(
                'id' => $team->getId(),
                'title' => (string)$team,
                'image' => $this->imageValues($team->getImage()),
                'fanCount' => $team->getFanCount()
            );

            $allowedfields = array(
            	'content', 'foundedAt', 'splash', 'categories', 'country', 'twitter', 'photoCount', 'videoCount', 'visitCount'
            );
            $extrafields = $this->getExtraFields($allowedfields);

            foreach ($extrafields as $x) {
                switch ($x) {
                    case 'foundedAt':
                        $return['foundedAt'] = $team->getFoundedAt() ? (int)$team->getFoundedAt()->format('U') : null;
                        break;
                    case 'splash':
                        $return['splash'] = $this->imageValues($team->getSplash());
                        break;
                    case 'country':
                        $return['country'] = $team->getCountry() ? $team->getCountry()->getId() : null;
                        break;
                    case 'categories':
                        $cats = $team->getTeamcategories();
                        $t = array();
                        foreach ($cats as $c) {
                            $t[] = array(
                                'id' => $c->getId(),
                                'title' => $c->getTitle(),
                                'sport' => $c->getSport()->getId()
                            );
                        }
                        $return[$x] = $t;
                        break;
                    default:
                        $methodname = 'get'.ucfirst($x);
                        $return[$x] = $team->$methodname();
                        break;
                }
            }

            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

    /**
     * [signed] Team - fan/unfan
     *
     * @Route("/team/fan/{action}", name="api_v1_team_fan", requirements = {"action" = "add|remove"})
     * @Method({"POST"})
     *
     * Post params:
	 * - user_id: int
	 * - team_id: int|array
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

                $teamids = $request->get('team_id');
                if (!is_array($teamids)) $teamids = array($teamids);
                if (array_unique($teamids) !== $teamids) throw new HttpException(400, 'Duplicate team_id');

                foreach ($teamids as $teamid) {
                    $team = $this->getRepository('Team')->find($teamid);
                    if (!$team) throw new HttpException(404, 'Team not found - id: ' . $teamid);

                    if ($action == 'add') {
                        $this->get('fanmaker')->addFan($team, $user, false);
                    } elseif ($action == 'remove') {
                        $this->get('fanmaker')->removeFan($team, $user);
                    } else {
                        throw new HttpException(400, 'Invalid fan action');
                    }
                }

                if ($action == 'add') {
                    $this->getDoctrine()->getEntityManager()->flush();
                }

                return $this->result(true);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

    /**
     * [signed] Team fans
     *
     * @Route("/team/{id}/fans", name="api_v1_team_fans", requirements = {"id" = "\d+"})
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
    public function teamFansListAction($id)
    {
        try {
                $request = $this->getRequest();
                $userid = $request->get('user_id');

                if (!$id) throw new HttpException(400, 'Invalid team id');
                $team = $this->getRepository('Team')->findOneBy(array('id' => $id));
                if (!$team) throw new HttpException(404, "Team not found");

                if ($userid) {
                    $user = $this->checkUserToken($userid, $request->get('user_token'));
                    if (!($user instanceof User)) throw new HttpException(404, 'User not found');
                }

                $pagination = $this->pagination();
                $pagination['sort_order'] = null;
                $pagination['sort'] = null;

                $imageformat = $request->get('imageformat');
                if (null == $imageformat) $imageformat = 'small';

                $fansOfTeam = $this->getRepository('User')->byTeams($team, $pagination['limit'], 'score', $pagination['offset']);

                $response = array();
                foreach ($fansOfTeam as $aFan) {
                    if($aFan->getId() != $userid) {
                        $response[] = $this->userArray($aFan);
                    }
                }

                if ($userid) {
                    $friendsOfUser = $this->getRepository('User')->FriendUsers($user, null, null, null, 'score');
                    $friendsIds = array();
                    foreach ($friendsOfUser as $friend) $friendsIds[] = $friend->getId();
                    foreach ($response as &$rta) {
                        in_array($rta['id'], $friendsIds) ? $rta['followed'] = true : $rta['followed'] = false;
                    }
                }

                return $this->result($response, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
