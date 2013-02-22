<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Benchmark controller.
 * @Route("/bench")
 */
class BenchController extends SiteController
{

    /**
     * HTTP (full) caching
     * @Cache(maxage="120")
     * @Template("DodiciFansworldWebBundle:bench:teamlist.html.twig")
     * @Route("/cache/http", name="admin_bench_httpcache")
     */
    public function httpCacheAction()
    {
        $teams = $this->getRepository('Team')->matching(null, null, null, 10, null, null, false);
        
        return array('teams' => $teams);
    }
    
	/**
     * Doctrine result caching
     * @Template("DodiciFansworldWebBundle:bench:teamlist.html.twig")
     * @Route("/cache/result", name="admin_bench_resultcache")
     */
    public function resultCacheAction()
    {
        $teams = $this->getRepository('Team')->matching(null, null, null, 10, null, null, true);
        
        return array('teams' => $teams);
    }
    
	/**
     * No caching
     * @Template("DodiciFansworldWebBundle:bench:teamlist.html.twig")
     * @Route("/cache/none", name="admin_bench_nocache")
     */
    public function noCacheAction()
    {
        $teams = $this->getRepository('Team')->matching(null, null, null, 10, null, null, false);
        
        return array('teams' => $teams);
    }
    
}
