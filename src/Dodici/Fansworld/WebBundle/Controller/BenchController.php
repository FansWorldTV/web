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
    
	/**
     * Mailing load
     * @Route("/mail/send", name="admin_bench_mailsend")
     */
    public function mailSendAction()
    {
        $this->get('fansworldmailer')->send('cr@fansworld.tv', '[BENCH] fw mail test', 'test');
        
        return new Response('Ok');
    }
    
    /**
     * Meteor load
     * @Route("/meteor/send", name="admin_bench_meteorsend")
     */
    public function meteorSendAction()
    {
        $noti = $this->getRepository('Notification')->find(1);
        $this->get('meteor')->push($noti);

        return new Response('Ok');
    }
    
	/**
     * Extract user videos
     * @Route("/video/extract", name="admin_bench_videoextract")
     */
    public function videoExtractAction()
    {
        $videos = array();
        
        $userids = $this->getRequest()->get('userid');
        
        foreach ($userids as $uid) {
            $usrvids = $this->getRepository('Video')->findBy(array('author' => $uid, 'active' => true));
            foreach ($usrvids as $uv) $videos[] = $uv;
        }
        $yml = '';
        foreach ($videos as $video) {
            if ($video->getAuthor()) {
                $yml .= "-\n";
                $yml .= "  author: " . $video->getAuthor()->getId();
            }
            
            if ($video->getStream()) {
                $yml .= "\n";
                $yml .= "  stream: " . $video->getStream();
                $yml .= "\n";
                $yml .= "  title: " . $video->getTitle();
            } elseif ($video->getYoutube()) {
                $yml .= "\n";
                $yml .= "  url: http://www.youtube.com/watch?v=" . $video->getYoutube();
            }
            
            $yml .= "\n";
            $yml .= "  highlight: " . ($video->getHighlight() ? 'true' : 'false');
            $yml .= "\n";
            $yml .= "  videocategory: " .$video->getVideoCategory()->getId();
            $yml .= "\n";
            $yml .= '  createdAt: "' . $video->getCreatedAt()->format('Y-m-d') . '"';
            $yml .= "\n";
            $yml .= "  tagidols:\n";
            foreach ($video->getHasidols() as $ti) $yml .= "      - " . $ti->getIdol()->getId() . "\n";
            $yml .= "  tagteams:\n";
            foreach ($video->getHasteams() as $ti) $yml .= "      - " . $ti->getTeam()->getId() . "\n";
            $yml .= "  tagtexts:\n";
            foreach ($video->getHastags() as $ti) $yml .= "      - " . $ti->getTag()->getTitle() . "\n";
            
            if ($video->getStream()) {
                $xp = explode("\n", $video->getContent());
                $yml .= "  content: |\n"; 
                foreach ($xp as $x) {
                    $yml .= "    ".($x)."\n";
                }
            }
        }
        
        $response = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
        $response .= '<textarea cols="80" rows="30">'.$yml.'</textarea>';
        $response .= '</body></html>';
        
        return new Response($response);
    }
    
}
