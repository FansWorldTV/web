<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Privacy;

use Dodici\Fansworld\WebBundle\Services\Search;

use Dodici\Fansworld\WebBundle\Entity\SearchHistory;

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
     * @Template("DodiciFansworldWebBundle:Bench:teamlist.html.twig")
     * @Route("/cache/http", name="admin_bench_httpcache")
     */
    public function httpCacheAction()
    {
        $teams = $this->getRepository('Team')->matching(null, null, null, 10, null, null, false);
        
        return array('teams' => $teams);
    }
    
	/**
     * Doctrine result caching
     * @Template("DodiciFansworldWebBundle:Bench:teamlist.html.twig")
     * @Route("/cache/result", name="admin_bench_resultcache")
     */
    public function resultCacheAction()
    {
        $teams = $this->getRepository('Team')->matching(null, null, null, 10, null, null, true);
        
        return array('teams' => $teams);
    }
    
	/**
     * No caching
     * @Template("DodiciFansworldWebBundle:Bench:teamlist.html.twig")
     * @Route("/cache/none", name="admin_bench_nocache")
     */
    public function noCacheAction()
    {
        $teams = $this->getRepository('Team')->matching(null, null, null, 10, null, null, false);
        
        return array('teams' => $teams);
    }
    
	/**
     * Database heavy load
     * @Route("/db/load", name="admin_bench_nocache")
     */
    public function dbLoadAction()
    {
        $teams = $this->getRepository('Team')->findBy(array('active' => true));
        $idols = $this->getRepository('Idol')->findBy(array('active' => true));
        $users = $this->getRepository('User')->findBy(array('restricted' => false, 'enabled' => true));
        $videos = $this->getRepository('Video')->findBy(array('active' => true, 'privacy' => Privacy::EVERYONE));
        $videocategories = $this->getRepository('VideoCategory')->findAll();
        $photos = $this->getRepository('Photo')->findBy(array('active' => true, 'privacy' => Privacy::EVERYONE));
        
        $user = $this->getRepository('User')->find(1);
        $em = $this->getDoctrine()->getEntityManager();
        
        for ($x = 0; $x < 10; $x++) {
            $sh = new SearchHistory();
            $sh->setAuthor($user);
            $sh->setIp('127.0.0.1');
            $sh->setTerm('*BENCHMARK*');
            $sh->setType(Search::TYPE_VIDEO);
            $em->persist($sh);
        }
        
        $em->flush();
        
        return new Response('Ok');
    }
}
