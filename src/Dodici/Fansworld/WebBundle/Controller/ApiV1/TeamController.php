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
 * API controller - Util
 * V1
 * @Route("/api_v1")
 */
class TeamController extends BaseController
{
	/**
     * Team - list
     * 
     * @Route("/team/list", name="api_team_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> country: int (country id to filter by)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'fanCount'|'title' (default: fanCount)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * 
     * @return 
     * array (
     * 		array (
     * 			id: int,
     * 			title: string,
     * 			image: string (url of image)
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
                    'image' => $team->getImage() ? $this->get('appmedia')->getImageUrl($team->getImage()) : null,
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
     * @Route("/team/{id}", name="api_team_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
	 * - <optional> extra_fields: comma-separated extra fields to return (see below)
     * 
     * @return 
     * array (
     * 			id: int,
     * 			title: string,
     * 			image: string (url of image)
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
                'image' => $team->getImage() ? $this->get('appmedia')->getImageUrl($team->getImage()) : null,
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
                        $return['splash'] = $team->getSplash() ? $this->get('appmedia')->getImageUrl($team->getSplash()) : null;
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
     * @Route("/team/fan/{action}", name="api_team_fan", requirements = {"action" = "add|remove"})
     * @Method({"POST"})
     *
     * Post params:
	 * - user_id: int
	 * - team_id: int
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
                $team = $this->getRepository('Team')->find($request->get('team_id'));
                if (!$team) throw new HttpException(404, 'Team not found');
                
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                if ($action == 'add') {
                    $this->get('fanmaker')->addFan($team, $user);
                } elseif ($action == 'remove') {
                    $this->get('fanmaker')->removeFan($team, $user);
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
