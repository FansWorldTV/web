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
class IdolController extends BaseController
{
	/**
     * Idol - list
     * 
     * @Route("/idol/list", name="api_idol_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> country: int (country id to filter by)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'fanCount'|'name' (default: fanCount)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * 
     * @return 
     * array (
     * 		array (
     * 			id: int,
     * 			firstname: string,
     * 			lastname: string,
     * 			image: string (url of image),
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
                    'image' => $idol->getImage() ? $this->get('appmedia')->getImageUrl($idol->getImage()) : null,
                    'fanCount' => $idol->getFanCount()
                );
            }
            
            return $this->jsonResponse($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * Idol - show
     * 
     * @Route("/idol/{id}", name="api_idol_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
	 * - <optional> extra_fields: comma-separated extra fields to return (see below)
     * 
     * @return 
     * array (
     * 			id: int,
     * 			firstname: string,
     * 			lastname: string,
     * 			image: string (url of image),
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
            $idol = $this->getRepository('Idol')->find($id);
            if (!$idol) throw new HttpException(404, 'Idol not found');
            
            $return = array(
                'id' => $idol->getId(),
                'firstname' => $idol->getFirstname(),
            	'lastname' => $idol->getLastname(),
                'image' => $idol->getImage() ? $this->get('appmedia')->getImageUrl($idol->getImage()) : null,
                'fanCount' => $idol->getFanCount()
            );
            
            $allowedfields = array(
            	'content', 'birthday', 'splash', 'country', 'sex', 'twitter', 'careers', 'photoCount', 'videoCount', 'visitCount'
            );
            $extrafields = $this->getExtraFields($allowedfields);
            
            foreach ($extrafields as $x) {
                switch ($x) {
                    case 'birthday':
                        $return['birthday'] = $idol->getBirthday() ? $idol->getBirthday()->format('U') : null;
                        break;
                    case 'splash':
                        $return['splash'] = $idol->getSplash() ? $this->get('appmedia')->getImageUrl($idol->getSplash()) : null;
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
                                    'date_from' => $c->getDateFrom() ? $c->getDateFrom()->format('U') : null,
                                    'date_to' => $c->getDateTo() ? $c->getDateTo()->format('U') : null,
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
            
            return $this->jsonResponse($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
