<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\EventTweet;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Batch controller.
 * @Route("/batch")
 */
class BatchController extends SiteController
{

    /**
     * Feed the Event fixture
     * @Route("/eventfeeding", name= "admin_batch_eventfeeding")
     */
    public function eventFeedingAction()
    {
        set_time_limit(600);
        $df = $this->get('feeder.event');
        $df->feed();
		$df->pending();
		
		return new Response('Ok');
    }
    
	/**
     * Feed event incidents
     * @Route("/eventminutefeeding", name= "admin_batch_eventminutefeeding")
     */
    public function eventMinuteFeedingAction()
    {
        $df = $this->get('feeder.event.minute');
        $df->feed();
		$df->pending();
		
		return new Response('Ok');
    }
    
	/**
     * Retrieve event tweets
     * @Route("/eventtweets", name= "admin_batch_eventtweets")
     */
    public function eventTweetsAction()
    {
        $teams = $this->getRepository('Team')->withEvents(5);
        $eventtweetrepo = $this->getRepository('EventTweet');
        $em = $this->getDoctrine()->getEntityManager();
        
        $topush = array();
        
        foreach ($teams as $t) {
            $team = $t['team'];
            $event = $t['event'];
            
            $maxtweetid = $eventtweetrepo->maxExternal($team);
            $twitter = $team->getTwitter();
            
            $apptwitter = $this->get('app.twitter');
            
            $latest = $apptwitter->latestSinceId($twitter, $maxtweetid);
            
            foreach ($latest as $l) {
                if ($l && is_object($l)) {
                    $date = new \DateTime($l->created_at);
                    $external = $l->id_str;
                    $content = $l->text;
                    
                    $exists = $eventtweetrepo->countBy(array('external' => $external));
                    
                    if (!$exists) {
                        $et = new EventTweet();
                        $et->setTeam($team);
                        $et->setEvent($event);
                        $et->setCreatedAt($date);
                        $et->setExternal($external);
                        $et->setContent($content);
                        
                        $em->persist($et);
                        
                        $topush[] = $et;
                    }
                }
            }
            
            $em->flush();
        }
        
        $meteor = $this->get('meteor');
        foreach ($topush as $tp) $meteor->push($tp);
		
		return new Response('Ok');
    }

	/**
     * Process pending videos (thumbnail, upload, etc)
     * @Route("/videoprocessing", name= "admin_batch_videoprocessing")
     */
    public function videoProcessingAction()
    {
        set_time_limit(600);
        $videos = $this->getRepository('Video')->pendingProcessing(10);
        $uploader = $this->get('video.uploader');
        
        foreach ($videos as $video) {
            $uploader->process($video);
        }
        
        return new Response('Ok');
    }
    
	/**
     * Clean up timed out users from "watching video" lists
     * @Route("/videoaudienceclean", name= "admin_batch_videoaudienceclean")
     */
    public function videoAudienceCleanAction()
    {
        $this->get('video.audience')->cleanup();
        
        return new Response('Ok');
    }
    
    /**
     * Convert CSV fixture files to YML
     * Ask before running
     * @Route("/fixturecsvtoyml", name="admin_batch_csvtoyml")
     */
    public function convertCSVtoYML()
    {
        $this->get('fixture.csvtoyml')->convertAll();
        
        return new Response('Ok');
    }
    
}
