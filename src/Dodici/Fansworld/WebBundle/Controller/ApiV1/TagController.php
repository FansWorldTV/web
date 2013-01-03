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
 * API controller - Tags
 * V1
 * @Route("/api_v1")
 */
class TagController extends BaseController
{
	/**
     * Trending tags
     * 
     * @Route("/tag/trending", name="api_tag_trending")
     * @Method({"GET"})
     *
     * Get params:
	 * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * 
     * @return 
     * array (
     * 		array(
     * 			id: int,
     * 			title: string,
     * 			slug: string,
     * 			type: 'tag'|'idol'|'team',
     * 			count: int
     * 		),
     * 		...
     * )
     */
    public function trendingAction()
    {
        try {
            $request = $this->getRequest();
            $pagination = $this->pagination();
            $pagination['sort_order'] = null;
            $pagination['sort'] = null;
            
            $tags = $this->get('tagger')->trending(
                $pagination['limit']
            );
            $return = $this->get('serializer')->values($tags);
            
            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
