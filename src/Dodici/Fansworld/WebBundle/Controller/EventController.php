<?php

namespace Dodici\Fansworld\WebBundle\Controller;

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
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Eventship;
use Dodici\Fansworld\WebBundle\Entity\EventIncident;
use Dodici\Fansworld\WebBundle\Entity\EventTweet;

/**
 * Event controller.
 * @Route("/event")
 */
class EventController extends SiteController
{
    const LIMIT_EVENTS = 12;
    
    /**
     * @Route("/{id}/{slug}", name= "event_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template
     */
    public function showAction($id)
    {
        //TODO: todo
        $event = $this->getRepository('Event')->findOneBy(array('id' => $id));
        $user = $this->getUser();
        $this->securityCheck($event);

        return array('user' => $user, 'event' => $event);
    }
    
    
    
    /**
     * @Route("/ajax/get", name= "event_get")
     */
    public function getAjaxEventsAction()
    {
        $request = $this->getRequest();
        $appMedia = $this->get('appmedia');
        
        $dateFrom    = $request->get('dateFrom', null);
        if($dateFrom != null && $dateFrom != 'null'){
            $dateFrom = \DateTime::createFromFormat('d/m/Y',$dateFrom);
            $dateFrom->setTime(0, 0);
        }else{
            $dateFrom = null;
        }
        
        $dateTo    = $request->get('dateTo', null);
        if($dateTo != null && $dateTo != 'null'){
            $dateTo = \DateTime::createFromFormat('d/m/Y',$dateTo);
            $dateTo->setTime(23, 59);
        }else{
            $dateTo = null;
        }
        
        $sortBy    = $request->get('sortBy', null);
        
        $sport     = $request->get('sport', null);
        if($sport){
            $sport = $this->getRepository('Sport')->findOneBy(array('id' => $sport));
        }
        
        $teamcategory     = $request->get('teamcategory', null);
        if($teamcategory){
            $teamcategory = $this->getRepository('TeamCategory')->findOneBy(array('id' => $teamcategory));
        }
        
        $response = array();
       
        $events  = $this->getRepository('Event')->calendar(null,null,null,$dateFrom,$dateTo,$sport,$teamcategory,$sortBy,null,null);
        foreach ($events as $event) {
            
            $teams = array();
            foreach ($event->getHasteams() as $hasTeam) {
                $teams[] = array(
                    'hasTeam' => $hasTeam,
                    'team'    => $hasTeam->getTeam()         
                ); 
            }
            
            $response[] = array(
                    'text' =>  $this->get('appstate')->getEventText($event->getId()),
                    'title' => $event->getTitle(),
                    'date'  => $event->getFromtime()->format('d-m-Y'),
                    'team1Score' => $teams[0]['hasTeam']->getScore(),
                    'team1Shortname' => $teams[0]['team']->getShortname(),
                    'team1Title' => $teams[0]['team']->getTitle(),
                    'team1Image' => $appMedia->getImageUrl($teams[0]['team']->getImage(), 'mini_square'),
                    
                    'team2Score' => $teams[1]['hasTeam']->getScore(),
                    'team2Shortname' => $teams[1]['team']->getShortname(),
                    'team2Title' => $teams[1]['team']->getTitle(),
                    'team2Image' => $appMedia->getImageUrl($teams[1]['team']->getImage(), 'mini_square'),
            );
        }
        
        return $this->jsonResponse($response);
    }
    
    
    /**
     * @Route("/ajax/getmonth", name= "event_getmonth")
     */
    public function getMonthEventsAction()
    {
        $request = $this->getRequest();
        $year    = $request->get('year', false);
        $month   = $request->get('month', false);
        $dateFrom = new \DateTime("-1 year");
        $dateTo = new \DateTime("+1 year");
        $response = array();
        $events  = $this->getRepository('Event')->calendar(null,null,null,$dateFrom,$dateTo,null,null,null);
        foreach ($events as $event) {
            $response[] = array(
                'id' => $event->getId(),
                'fecha' => $event->getFromtime()->format('d-m-Y')
            );
        }
        return $this->jsonResponse($response);
    }
    
    /**
     * @Route("", name= "event_home" )
     * @Template
     */
    public function homeTabAction()
    {
        //TODO: todo
        $eventRepo = $this->getRepository('Event');
        //$events = $eventRepo->findBy(array(), array('fromtime' => 'desc'),self::LIMIT_EVENTS,1);
        $events = null;
        $eventoDestacado = $eventRepo->findOneBy(array(), array('fromtime' => 'desc'));
        
        $sports = $this->getRepository('Sport')->findBy(array());
        $leagues = true;
        $orderBy = true;
        
        return array(
            'eventoDestacado' => $eventoDestacado,
            'events' => $events,        
            'sports' => $sports,
            'leagues' => $leagues,
            'orderBy' => $orderBy,    
        );
    }
    
    /**
     * @Route("/checkin/{id}", name="event_checkin", requirements = {"id" = "\d+"}) 
     * @Template
     */
    public function checkInAction($id)
    {
        $id = (int) $id;
        $event = $this->getRepository('Event')->find($id);
        $teams = array();
        foreach ($event->getHasteams() as $team) {
            array_push($teams, array($team->getId() => (string) $team->getTeam()));
        }
        return array('event' => $id, 'teams' => $teams);
    }

    /**
     *  @Route("/ajax/checkin", name="event_checkinajax")
     */
    public function doCheckInAction()
    {
        $request = $this->getRequest();
        $eventId = $request->get('event', false);
        $type = $request->get('type', false);
        $teamId = $request->get('teamId', false);

        $type = (int) $type;
        $author = $this->getUser();
        $event = $this->getRepository('Event')->find($eventId);
        
        if ($teamId) {
            $teamId = (int) $teamId;
            $team = $this->getRepository('Team')->find($teamId);
        }

        $response = array();

        try {
            $eventShip = new Eventship();

            $eventShip->setAuthor($author);
            $eventShip->setEvent($event);
            $eventShip->setType($type);
            
            if($teamId){
                $eventShip->setTeam($team);
            }

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($eventShip);
            $em->flush();

            $response['error'] = false;
        } catch (Exception $exc) {
            $response['error'] = true;
            $response['msg'] = $exc->getMessage();
        }

        return $this->jsonResponse($response);
    }
    
    
    /**
     * @Route("/getincident", name= "event_getincident")
     * @Template
     */
    public function getIncidentAction()
    {
        $request = $this->getRequest();
        $incidentid = $request->get('incidentid', false);
        
        //TODO: todo
        $incident = $this->getRepository('EventIncident')->findOneBy(array('id' => $incidentid));
        $response = array(
            'team' => $incident->getTeam()->getId(),
            'minute' => $this->get('appstate')->getMinuteFromTimestamp($incident->getCreatedAt()),
            'texto1' => "GOL!",
            'texto2' => $incident->getName(),
            'texto3' => $incident->getMinute().'\' '.$incident->getHalf(),
        );
        return $this->jsonResponse($response);
    }
    
    /**
     * @Route("/gettweet", name= "event_gettweet")
     * @Template
     */
    public function getTweetAction()
    {
        $request = $this->getRequest();
        $tweetid = $request->get('tweetid', false);
    
        //TODO: todo
        $tweet = $this->getRepository('EventTweet')->findOneBy(array('id' => $tweetid));
        $response = array(
            'teamid' => $tweet->getTeam()->getId(),
            'teamname' => $tweet->getTeam()->getTwitter(),
            'minute' => $this->get('appstate')->getMinuteFromTimestamp($tweet->getCreatedAt()),
            'content' =>  $tweet->getContent(),
        );
        return $this->jsonResponse($response);
    }
    
    /**
     * @Route("/getcomment", name= "event_getcomment")
     * @Template
     */
    public function getCommentAction()
    {
        $request = $this->getRequest();
        $commentId = $request->get('commentid', false);
    
        //TODO: todo
        $comment = $this->getRepository('Comment')->findOneBy(array('id' => $commentId));
        $response = array(
            'teamid' => $comment->getTeam()->getId(),
            'avatar' => $this->getImageUrl($comment->getAuthor()->getImage()),
            'minute' => $this->get('appstate')->getMinuteFromTimestamp($comment->getCreatedAt()),
            'content' =>  $comment->getContent(),
        );
        return $this->jsonResponse($response);
    }
    
    
    /*
      borrar cuando no se necesiten mas
      
    */
    
    
    /**
     * @Route("/addincident/{eventid}/{teamid}", name= "event_addincident")
     * @Template
     */
    public function addIncidentAction($eventid,$teamid)
    {
        $em = $this->getDoctrine()->getEntityManager();
        //TODO: todo
        $event = $this->getRepository('Event')->findOneBy(array('id' => $eventid));
        $team = $this->getRepository('Team')->findOneBy(array('id' => $teamid));
        $user = $this->getUser();
        $this->securityCheck($event);
    
        $incident = new EventIncident();
        $incident->setExternal('sdfsdfsdf');
        $incident->setType(1);
        $incident->setTeam($team);
        $incident->setPlayername('negro drogba');
        $incident->setMinute('5');
        $incident->setHalf('pt');
    
        $event->addEventIncident($incident);
    
        $em->persist($event);
        $em->flush();
        return $this->jsonResponse(array());
    }
    
    /**
     * @Route("/addtwincident/{eventid}/{teamid}", name= "event_addtwincident")
     * @Template
     */
    public function addTwIncidentAction($eventid,$teamid)
    {
       // new EventTweet(), setevent, setteam, setcontent. external es el id del tweet
        
        $em = $this->getDoctrine()->getEntityManager();
        //TODO: todo
        $event = $this->getRepository('Event')->findOneBy(array('id' => $eventid));
        $team = $this->getRepository('Team')->findOneBy(array('id' => $teamid));
        $et = new EventTweet();
        $et->setTeam($team);
        $et->setEvent($event);
        //$et->setCreatedAt($date);
        $et->setExternal('1232132131');
        $et->setContent('bla bla bla bla balblabla bla balb alab');
        $em->persist($et);
        $em->flush();
        
        return $this->jsonResponse(array());
        
    }
    
    /**
     * @Route("/addcomment/{eventid}/{teamid}", name= "event_addcomment")
     * @Template
     */
    public function addCommentAction($eventid,$teamid)
    {
        $em = $this->getDoctrine()->getEntityManager();
        //TODO: todo
        $event = $this->getRepository('Event')->findOneBy(array('id' => $eventid));
        $team = $this->getRepository('Team')->findOneBy(array('id' => $teamid));
        
        $user = $this->getUser();
        
    
        $comment = new Comment();
        $comment->setTeam($team);
        $comment->setEvent($event);
        $comment->setAuthor($user);
        $comment->setContent('negro drogba meta meta');
        
        $event->addComments($comment);
        $em->persist($event);
        $em->flush();
        return $this->jsonResponse(array());
    }
}
