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
 * API controller - Idol
 * V1
 * @Route("/api_v1")
 */
class IdolController extends BaseController
{
	/**
     * Idol - list
     *
     * @Route("/idol/list", name="api_v1_idol_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> country: int (country id to filter by)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'fanCount'|'name' (default: fanCount)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * - <optional> imageformat: string
     *
     * @return
     * array (
     * 		array (
     * 			id: int,
     * 			firstname: string,
     * 			lastname: string,
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

            $pagination = $this->pagination(array('fanCount', 'name'), 'fanCount');

            $filters = array('active' => true);

            if ($countryid) {
                $country = $this->getRepository('Country')->find($countryid);
                if (!$country) throw new HttpException(400, 'Invalid country');
                $filters['country'] = $country->getId();
            }

            $sortarray = array($pagination['sort'] => $pagination['sort_order']);
            if ($pagination['sort'] == 'name') $sortarray = array(
            	'lastname' => $pagination['sort_order'],
            	'firstname' => $pagination['sort_order']
            );

            $idols = $this->getRepository('Idol')->findBy(
                $filters,
                $sortarray,
                $pagination['limit'],
                $pagination['offset']);

            $return = array();

            foreach ($idols as $idol) {
                $return[] = array(
                    'id' => $idol->getId(),
                    'firstname' => $idol->getFirstname(),
                	'lastname' => $idol->getLastname(),
                    'image' => $this->imageValues($idol->getImage()),
                    'fanCount' => $idol->getFanCount()
                );
            }

            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

	/**
     * [signed if user_id given] Idol - show
     *
     * @Route("/idol/{id}", name="api_v1_idol_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
	 * - <optional> extra_fields: comma-separated extra fields to return (see below)
	 * - <optional> imageformat: string
     * - <optional> user_id: int
     * - <required if user_id given> [user token]
     * - [signature params if user_id given]
     *
     * @return
     * array (
     * 			id: int,
     * 			firstname: string,
     * 			lastname: string,
     * 			image: array(id: int, url: string),
     * 			fanCount: int,
     *
     * 			// extra fields
     * 			content: string,
     * 			birthday: int (DOB timestamp UTC),
     * 			splash: string (url of image),
     * 			country: int (country id),
     * 			sex: string ('m'|'f'),
     * 			twitter: string,
     * 			careers: array(
     * 				array(
     * 					team_id: int (null if not a team entity),
     *                  team_name: string,
     *                  position: string,
     *                  content: string,
     *                  date_from: int (ts UTC),
     *                  date_to: int (ts UTC),
     *                  debut: boolean (idol's first career),
     *                  actual: boolean (idol is still working there),
     *                  highlight: boolean (highlighted step in the idol's career),
     *                  manager: boolean (idol was a manager here)
     * 				),
     * 				...
     * 			),
     * 			photoCount: int,
     * 			videoCount: int,
     * 			visitCount: int
     * 		)
     *
     */
    public function showAction($id)
    {
        try {
            $request = $this->getRequest();
            $userid = $request->get('user_id');
            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }

            $idol = $this->getRepository('Idol')->find($id);
            if (!$idol) throw new HttpException(404, 'Idol not found');

            $return = array(
                'id' => $idol->getId(),
                'firstname' => $idol->getFirstname(),
            	'lastname' => $idol->getLastname(),
                'image' => $this->imageValues($idol->getImage()),
                'fanCount' => $idol->getFanCount(),
                'videoCount' => $idol->getVideoCount()
            );

            if ($user) {
                $return['followed'] = $this->get('fanmaker')->status($idol, $user);
            }

            $allowedfields = array(
            	'content', 'birthday', 'splash', 'country', 'sex', 'twitter', 'careers', 'photoCount', 'visitCount'
            );
            $extrafields = $this->getExtraFields($allowedfields);

            foreach ($extrafields as $x) {
                switch ($x) {
                    case 'birthday':
                        $return['birthday'] = $idol->getBirthday() ? $idol->getBirthday()->format('U') : null;
                        break;
                    case 'splash':
                        $return['splash'] = $this->imageValues($idol->getSplash());
                        break;
                    case 'country':
                        $return['country'] = $idol->getCountry() ? $idol->getCountry()->getId() : null;
                        break;
                    case 'careers':
                        $careers = $idol->getIdolcareers();
                        $t = array();
                        foreach ($careers as $c) {
                            if ($c->getActive()) {
                                $t[] = array(
                                    'team_id' => $c->getTeam() ? $c->getTeam()->getId() : null,
                                    'team_name' => $c->getTeam() ? (string)$c->getTeam() : $c->getTeamName(),
                                    'position' => $c->getPosition(),
                                    'content' => $c->getContent(),
                                    'date_from' => $c->getDateFrom() ? (int)$c->getDateFrom()->format('U') : null,
                                    'date_to' => $c->getDateTo() ? (int)$c->getDateTo()->format('U') : null,
                                    'debut' => $c->getDebut(),
                                    'actual' => $c->getActual(),
                                    'highlight' => $c->getHighlight(),
                                    'manager' => $c->getManager()
                                );
                            }
                        }
                        $return[$x] = $t;
                        break;
                    default:
                        $methodname = 'get'.ucfirst($x);
                        $return[$x] = $idol->$methodname();
                        break;
                }
            }

            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

	/**
     * [signed] Idol - fan/unfan
     *
     * @Route("/idol/fan/{action}", name="api_v1_idol_fan", requirements = {"action" = "add|remove"})
     * @Method({"POST"})
     *
     * Post params:
	 * - user_id: int
	 * - idol_id: int|array
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

                $idolids = $request->get('idol_id');
                if (!$idolids) throw new HttpException(400, 'Requires idol_id');
                if (!is_array($idolids)) $idolids = array($idolids);
                if (array_unique($idolids) !== $idolids) throw new HttpException(400, 'Duplicate idol_id');

                $updates = array();

                foreach ($idolids as $idolid) {
                    $idol = $this->getRepository('Idol')->find($idolid);
                    if (!$idol) throw new HttpException(404, 'Idol not found - id: ' . $idolid);

                    if ($action == 'add') {
                        $this->get('fanmaker')->addFan($idol, $user, false);
                    } elseif ($action == 'remove') {
                        $this->get('fanmaker')->removeFan($idol, $user);
                    } else {
                        throw new HttpException(400, 'Invalid fan action');
                    }

                    $updates[] = $idol;
                }

                if ($action == 'add') {
                    $this->getDoctrine()->getEntityManager()->flush();
                }

                $result = array();
                foreach ($updates as $ui) $result[] = array('id' => $ui->getId(), 'fanCount' => $ui->getFanCount(), 'followed' => $this->get('fanmaker')->status($idol, $user));

                return $this->result((count($result) == 1) ? $result[0] : $result);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }


    /**
     * [signed] Idol fans
     *
     * @Route("/idol/{id}/fans", name="api_v1_idol_fans", requirements = {"id" = "\d+"})
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
    public function idolFansListAction($id)
    {
        try {
                $request = $this->getRequest();
                $userid = $request->get('user_id');

                if (!$id) throw new HttpException(400, 'Invalid idol id');
                $idol = $this->getRepository('Idol')->findOneBy(array('id' => $id));
                if (!$idol) throw new HttpException(404, "Idol not found");

                if ($userid) {
                    $user = $this->checkUserToken($userid, $request->get('user_token'));
                    if (!($user instanceof User)) throw new HttpException(404, 'User not found');
                }

                $pagination = $this->pagination();
                $pagination['sort_order'] = null;
                $pagination['sort'] = null;

                $imageformat = $request->get('imageformat');
                if (null == $imageformat) $imageformat = 'small';

                $fansOfIdol = $this->getRepository('User')->byIdols($idol, $pagination['limit'], 'score', $pagination['offset']);

                $response = array();
                $friender = $this->get('friender');
                foreach ($fansOfIdol as $aFan) {
                    if($aFan->getId() != $userid) {
                        $arr = $this->userArray($aFan);
                        if ($userid) $arr['followed'] = $friender->status($aFan, $user);
                        $response[] = $arr;
                    }
                }

                return $this->result($response, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
