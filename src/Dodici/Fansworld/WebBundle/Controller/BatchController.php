<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\EventTweet;
use Kaltura\Client\Enum\EntryStatus;
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
     * @Route("/eventfeeding", name="admin_batch_eventfeeding")
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
     * @Route("/eventminutefeeding", name="admin_batch_eventminutefeeding")
     */
    public function eventMinuteFeedingAction()
    {
        $df = $this->get('feeder.event.minute');
        $df->feed();
		$df->pending();
		
		return new Response('Ok');
    }
    
    /**
     * Finish open, expired events (sanity check)
     * @Route("/eventfinishing", name="admin_batch_eventfinishing")
     */
    public function eventFinishingAction()
    {
        $events = $this->getRepository('Event')->expired();
        $em = $this->getDoctrine()->getEntityManager();
        
        if ($events) {
            foreach ($events as $event) {
                $event->setFinished(true);
                $em->persist($event);
            }
            
            $em->flush();
        }
        
        return new Response('Ok');
    }
    
	/**
     * Retrieve event tweets
     * @Route("/eventtweets", name="admin_batch_eventtweets")
     */
    public function eventTweetsAction()
    {
        $teams = $this->getRepository('Team')->withEvents(1);
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
     * @Route("/videoprocessing", name="admin_batch_videoprocessing")
     */
    public function videoProcessingAction()
    {
        set_time_limit(600);
        $videos = $this->getRepository('Video')->pendingProcessing(10);
        $uploader = $this->get('video.uploader');
        $kaltura = $this->get('kaltura');
        
        foreach ($videos as $video) {
            try {
                $entry = $kaltura->getEntry($video->getStream());
                if ($entry && ($entry->status == EntryStatus::READY)) {
                    $uploader->process($video, $entry->thumbnailUrl, $entry->msDuration);
                    if ($video->getHighlight()) $kaltura->setSiteMetadata($video->getStream());
                    else $kaltura->setUserMetadata($video->getStream());
                }
            } catch (\Exception $e) {
                // entry doesn't exist or something went wrong, do nothing for now
            }
        }
        
        return new Response('Ok');
    }
    
	/**
     * Clean up timed out users from "watching video" lists
     * @Route("/videoaudienceclean", name="admin_batch_videoaudienceclean")
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
    
    /**
     * Update video/photocounts
     * @Route("/updatecounts", name="admin_batch_updatecounts")
     */
    public function updateCounts()
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        $idols = $this->getRepository('Idol')->findAll();
        foreach ($idols as $idol) {
            $cntvideo = $this->getRepository('Idol')->countTagged($idol, 'video');
            $cntphoto = $this->getRepository('Idol')->countTagged($idol, 'photo');
            $cntfans = $this->getRepository('Idolship')->countBy(array('idol' => $idol->getId()));
            $idol->setVideoCount($cntvideo);
            $idol->setPhotoCount($cntphoto);
            $idol->setFanCount($cntfans);
            $em->persist($idol);
        }
        
        $teams = $this->getRepository('Team')->findAll();
        foreach ($teams as $team) {
            $cntvideo = $this->getRepository('Team')->countTagged($team, 'video');
            $cntphoto = $this->getRepository('Team')->countTagged($team, 'photo');
            $cntfans = $this->getRepository('Teamship')->countBy(array('team' => $team->getId()));
            $team->setVideoCount($cntvideo);
            $team->setPhotoCount($cntphoto);
            $team->setFanCount($cntfans);
            $em->persist($team);
        }
        
        $users = $this->getRepository('User')->findBy(array('enabled' => true));
        foreach ($users as $user) {
            $cntvideo = $this->getRepository('Video')->countBy(array('author' => $user->getId(), 'active' => true));
            $cntphoto = $this->getRepository('Photo')->countBy(array('author' => $user->getId(), 'active' => true));
            $cntfans = $this->getRepository('Friendship')->countBy(array('target' => $user->getId(), 'active' => true));
            $user->setVideoCount($cntvideo);
            $user->setPhotoCount($cntphoto);
            $user->setFanCount($cntfans);
            $em->persist($user);
        }
        
        $em->flush();
        
        return new Response('Ok');
    }
    
}
