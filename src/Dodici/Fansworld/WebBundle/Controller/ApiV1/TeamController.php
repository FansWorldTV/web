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
            $allowedsorts = array('fanCount', 'title');
            $allowedorders = array('ASC', 'DESC');
            
            $request = $this->getRequest();
            $countryid = $request->get('country');
            $limit = $request->get('limit', self::LIMIT_DEFAULT);
            $offset = $request->get('offset');
            $page = $request->get('page');
            $sort = $request->get('sort', 'fanCount');
            $sortorder = $request->get('sort_order', 'DESC');
            $sortorder = strtoupper($sortorder);
                        
            if ($offset && $page) throw new HttpException(400, 'Cannot specify both offset and page at the same time');
            if (!in_array($sort, $allowedsorts)) throw new HttpException(400, 'Invalid sort');
            if (!in_array($sortorder, $allowedorders)) throw new HttpException(400, 'Invalid sort_order');
            if ($limit && !is_numeric($limit)) throw new HttpException(400, 'Invalid limit');
            if ($offset && !is_numeric($offset)) throw new HttpException(400, 'Invalid offset');
            
            if ($page) $offset = $page * $limit;
            
            $filters = array('active' => true);
            
            if ($countryid) {
                $country = $this->getRepository('Country')->find($countryid);
                if (!$country) throw new HttpException(400, 'Invalid country');
                $filters['country'] = $country->getId();
            }
            
            if ($sort == 'title') $sort = 'shortname';
            
            $teams = $this->getRepository('Team')->findBy(
                $filters, 
                array($sort => $sortorder),
                $limit,
                $offset);
                
            $return = array();
            
            foreach ($teams as $team) {
                $return[] = array(
                    'id' => $team->getId(),
                    'title' => (string)$team,
                    'image' => $team->getImage() ? $this->get('appmedia')->getImageUrl($team->getImage()) : null,
                    'fanCount' => $team->getFanCount()
                );
            }
            
            return $this->jsonResponse($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
