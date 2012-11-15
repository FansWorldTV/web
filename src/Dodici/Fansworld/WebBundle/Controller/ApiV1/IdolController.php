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
}
