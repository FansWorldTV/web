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
 * API controller - Search
 * V1
 * @Route("/api_v1")
 */
class SearchController extends BaseController
{
	/**
     * Popular history
     * 
     * @Route("/search/history", name="api_v1_search_history")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> match: string (partial match)
	 * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * 
     * @return 
     * array (
     * 		array(
     * 			term: string,
     * 			cnt: int
     * 		),
     * 		...
     * )
     */
    public function historyAction()
    {
        try {
            $request = $this->getRequest();
            $match = $request->get('match');
            $pagination = $this->pagination();
            $pagination['sort_order'] = null;
            $pagination['sort'] = null;
            
            $terms = $this->get('search')->topTerms(
                $match,
                null, null, null,
                $pagination['limit'],
                $pagination['offset']
            );
            $return = $this->get('serializer')->values($terms);
            
            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
