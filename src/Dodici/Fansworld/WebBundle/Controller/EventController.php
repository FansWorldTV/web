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
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Event controller.
 * @Route("/event")
 */
class EventController extends SiteController
{

    const LIMIT_EVENTS = 12;

    /**
     * @Route("/{id}/{slug}", name= "event_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function showAction($id)
    {
        $event = $this->getRepository('Event')->find($id);
        $user = $this->getUser();
        $this->securityCheck($event);

        $local = null;
        $guest = null;
        foreach ($event->getHasteams() as $team) {
            if (is_null($local)) {
                $local = $team->getTeam();
            } else {
                $guest = $team->getTeam();
            }
        }
        $eshipsLocal = $this->getRepository('Eventship')->findBy(array('team' => $local->getId(), 'event' => $event->getId()));
        $eshipsGuest = $this->getRepository('Eventship')->findBy(array('team' => $guest->getId(), 'event' => $event->getId()));
        
        $userChecked = $this->getRepository('Eventship')->findOneBy(array('event' => $event->getId(), 'author'=> $user->getId()));

        return array('user' => $user, 'entity' => $event, 'eshipsLocal' => $eshipsLocal, 'eshipsGuest' => $eshipsGuest, 'checked' => $userChecked);
    }

    /**
     * @Route("/ajax/get", name= "event_get")
     */
    public function getAjaxEventsAction()
    {
        $request = $this->getRequest();
        $appMedia = $this->get('appmedia');

        $dateFrom = $request->get('dateFrom', null);
        if ($dateFrom != null && $dateFrom != 'null') {
            $dateFrom = \DateTime::createFromFormat('d/m/Y', $dateFrom);
            $dateFrom->setTime(0, 0);
        } else {
            $dateFrom = null;
        }

        $dateTo = $request->get('dateTo', null);
        if ($dateTo != null && $dateTo != 'null') {
            $dateTo = \DateTime::createFromFormat('d/m/Y', $dateTo);
            $dateTo->setTime(23, 59);
        } else {
            $dateTo = null;
        }

        $sortBy = $request->get('sortBy', null);
        if ($sortBy == 'null')
            $sortBy = null;

        $sport = $request->get('sport', null);
        if ($sport == 'null')
            $sport = null;
        if ($sport) {
            $sport = $this->getRepository('Sport')->findOneBy(array('id' => $sport));
        }

        $teamcategory = $request->get('teamcategory', null);
        if ($teamcategory == 'null')
            $teamcategory = null;
        if ($teamcategory) {
            $teamcategory = $this->getRepository('TeamCategory')->findOneBy(array('id' => $teamcategory));
        }

        $response = array();
        $now = new \DateTime();

        $events = $this->getRepository('Event')->calendar(null, null, null, $dateFrom, $dateTo, $sport, $teamcategory, $sortBy, null, null);
        foreach ($events as $event) {

            $teams = array();
            foreach ($event->getHasteams() as $hasTeam) {
                $team = $hasTeam->getTeam();
                $teams[] = array(
                    'title' => (string) $team,
                    'image' => $appMedia->getImageUrl($team->getImage(), 'mini_square'),
                    'score' => $hasTeam->getScore()
                );
            }

            $started = ($event->getFromtime() <= $now);

            if($this->getUser() instanceof User){
                $checked = $this->getRepository('Eventship')->findOneBy(array('author' => $this->getUser()->getId(), 'event' => $event->getId())) ? true : false;
            }else{
                $checked = null;
            }

            $response[] = array(
                'text' => $this->get('appstate')->getEventText($event->getId()),
                'id' => $event->getId(),
                'stadium' => $event->getStadium(),
                'date' => $event->getFromtime()->format('d-m-Y'),
                'showdate' => $event->getFromtime()->format('d/m/Y H:i'),
                'started' => $started,
                'finished' => $event->getFinished(),
                'teams' => $teams,
                'url' => $this->generateUrl('event_show', array('id' => $event->getId(), 'slug' => $event->getSlug())),
                'checked' => $checked
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/ajax/comment", name="event_ajaxcomment")
     */
    public function ajaxCommentAction()
    {
        $request = $this->getRequest();

        $response = array(
            'error' => false,
            'msg' => null
        );

        $eventId = $request->get('event-id', false);
        $teamId = $request->get('team-id', false);
        $text = $request->get('text', false);

        if (!$eventId || !$teamId || !$text) {
            $response['error'] = true;
            $response['msg'] = "Empty field";
        } else {
            try {
                $em = $this->getDoctrine()->getEntityManager();
                $event = $this->getRepository('Event')->find($eventId);
                $team = $this->getRepository('Team')->find($teamId);

                $user = $this->getUser();

                $comment = new Comment();
                $comment->setTeam($team);
                $comment->setEvent($event);
                $comment->setAuthor($user);
                $comment->setContent($text);

                $event->addComments($comment);
                $em->persist($event);
                $em->flush();
            } catch (\Exception $exc) {
                $response['error'] = true;
                $response['msg'] = $exc->getMessage();
            }
        }


        return $this->jsonResponse($response);
    }

    /**
     * @Route("/ajax/getmonth", name= "event_getmonth")
     */
    public function getMonthEventsAction()
    {
        $request = $this->getRequest();
        $year = $request->get('year', false);
        $month = $request->get('month', false);

        if (!$year || !$month)
            throw new \Exception('Must provide year and month');
        if (!is_numeric($year) || !is_numeric($month))
            throw new \Exception('Invalid month/year');

        $dateFrom = new \DateTime($year . '-' . sprintf('%02d', $month) . '-' . '01');
        if (!$dateFrom)
            throw new \Exception('Invalid month/year');

        // DateInterval adding is a bit buggy, so let's do it by hand
        $newmonth = $month + 1;
        $newyear = $year;
        if ($newmonth > 12) {
            $newyear++;
            $newmonth -= 12;
        }
        $dateTo = new \DateTime($newyear . '-' . sprintf('%02d', $newmonth) . '-' . '01');

        $response = array();
        $events = $this->getRepository('Event')->calendar(null, null, null, $dateFrom, $dateTo, null, null, null);
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
        $eventRepo = $this->getRepository('Event');
        $events = null;
        $eventoDestacado = $eventRepo->findOneBy(array(), array('fromtime' => 'desc'));
        
        if($this->getUser() instanceof User){
            $eventoDestacadoChecked = $this->getRepository('Eventship')->findOneBy(array('event' => $eventoDestacado->getId(), 'author' => $this->getUser()->getId())) ? true : false;
        }else{
            $eventoDestacadoChecked = null;
        }
        
        $sports = $this->getRepository('Sport')->findBy(array());
        $leagues = true;
        $orderBy = true;

        return array(
            'eventoDestacado' => $eventoDestacado,
            'eventoDestacadoChecked' => $eventoDestacadoChecked,
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
        foreach ($event->getHasteams() as $hasTeam) {
            array_push($teams, $hasTeam->getTeam());
        }
        $eventshipLocal = $this->getRepository('Eventship')->countBy(array('event' => $event->getId(), 'team' => $teams[0]->getId()));
        $eventshipGuest = $this->getRepository('Eventship')->countBy(array('event' => $event->getId(), 'team' => $teams[1]->getId()));
        return array('event' => $id, 'teams' => $teams, 'eventshipLocal' => $eventshipLocal, 'eventshipGuest' => $eventshipGuest);
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

        $eventship = $this->getRepository('Eventship')->findBy(array('event' => $eventId, 'author' => $author->getId()));

        if (!$eventship) {
            try {
                $eventShip = new Eventship();

                $eventShip->setAuthor($author);
                $eventShip->setEvent($event);
                $eventShip->setType($type);

                if ($teamId) {
                    $eventShip->setTeam($team);
                }

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($eventShip);
                $em->flush();

                $response['error'] = false;
            } catch (\Exception $exc) {
                $response['error'] = true;
                $response['msg'] = $exc->getMessage();
            }
        } else {
            $response['error'] = true;
            $response['msg'] = "Ya estas participando.";
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/getwallelements", name= "event_getwallelements")
     * @Template
     */
    public function getWallElementsAction()
    {
        $request = $this->getRequest();
        $eventId = $request->get('eventid', false);
        $event = $this->getRepository('Event')->findOneBy(array('id' => $eventId));
        $response = array();

        $fromMinute = $request->get('fromMinute', null);
        if ($fromMinute != null && $fromMinute != 'null') {
            $mindate = $this->get('appstate')->getDatetimeFromMinute($fromMinute);
        } else {
            $mindate = null;
        }

        $toMinute = $request->get('toMinute', false);
        if ($toMinute != null && $toMinute != 'null') {
            $maxdate = $this->get('appstate')->getDatetimeFromMinute($toMinute);
        } else {
            $maxdate = null;
        }

        
        $eventComments = $this->getRepository('Comment')->eventWall($event, $mindate, $maxdate);
        foreach ($eventComments as $comment) {
            $this->addEntityResponse($comment, $event, $response);
        }
        
        $eventTweets = $this->getRepository('EventTweet')->eventWall($event, $mindate, $maxdate);
        foreach ($eventTweets as $tweet) {
            $this->addEntityResponse($tweet, $event, $response);
        }
        
        $eventIncidents = $this->getRepository('EventIncident')->eventWall($event, $mindate, $maxdate);
        foreach ($eventIncidents as $incident) {
            $this->addEntityResponse($incident, $event, $response);
        }
        
        return $this->jsonResponse($response);
    }

    private function addEntityResponse($entity, $event, &$to)
    {
        $appState = $this->get('appstate');
        $type = $appState->getType($entity);
        if($type != 'eventtweet' && $type != 'eventincident'){
            $eventship = $this->getRepository('Eventship')->findOneBy(array('author' => $entity->getAuthor()->getId(), 'event' => $event->getId()));
        }else{
            $eventship = false;
        }
        $to[] = $this->formatJson($entity, $eventship);
    }

    private function formatJson($entity, $eventship = false)
    {
        $appState = $this->get('appstate');
        $type = $appState->getType($entity);
        $response = array();

        if ($type == 'comment') {
            $response = array(
                'type' => 'c',
                'teamid' => $entity->getTeam()->getId(),
                'avatar' => $this->getImageUrl($entity->getAuthor()->getImage()),
                'minute' => $this->get('appstate')->getMinuteFromTimestamp($entity->getCreatedAt()),
                'content' => $entity->getContent()
            );
        } else if ($type == 'eventtweet') {
            $response = array(
                'type' => 'et',
                'teamid' => $entity->getTeam()->getId(),
                'teamname' => $entity->getTeam()->getTwitter(),
                'minute' => $this->get('appstate')->getMinuteFromTimestamp($entity->getCreatedAt()),
                'content' => $entity->getContent(),
            );
        } else if ($type == 'eventincident') {
            $response = array(
                'type' => 'ei',
                'team' => $entity->getTeam()->getId(),
                'minute' => $appState->getMinuteFromTimestamp($entity->getCreatedAt()),
                'texto1' => $appState->getEventIncidentTypeName($entity->getType()),
                'texto2' => $entity->getName(),
                'texto3' => $entity->getMinute() . '\' ' . $entity->getHalf(),
            );
        }

        if ($eventship) {
            $response['eventship'] = $eventship->getType();
        }

        return $response;
    }

    /**
     * @Route("/getincident", name= "event_getincident")
     * @Template
     */
    public function getIncidentAction()
    {
        $request = $this->getRequest();
        $incidentid = $request->get('incidentid', false);

        $incident = $this->getRepository('EventIncident')->findOneBy(array('id' => $incidentid));
        $response = $this->formatJson($incident);
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


        $tweet = $this->getRepository('EventTweet')->findOneBy(array('id' => $tweetid));
        $response = $this->formatJson($tweet);
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


        $comment = $this->getRepository('Comment')->findOneBy(array('id' => $commentId));
        $eventship = $this->getRepository('Eventship')->findOneBy(array('author' => $comment->getAuthor()->getId(), 'event' => $comment->getEvent()->getId()));
        $response = $this->formatJson($comment, $eventship);
        return $this->jsonResponse($response);
    }

    /*
      borrar cuando no se necesiten mas metodos hardcode para testear meteor

     */

    /**
     * @Route("/addincident/{eventid}/{teamid}", name= "event_addincident")
     * @Template
     */
    public function addIncidentAction($eventid, $teamid)
    {
        $em = $this->getDoctrine()->getEntityManager();

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
    public function addTwIncidentAction($eventid, $teamid)
    {
        // new EventTweet(), setevent, setteam, setcontent. external es el id del tweet

        $em = $this->getDoctrine()->getEntityManager();
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
     * @Route("/addcomment/{eventid}/{teamid}/{user}", name= "event_addcomment")
     * @Template
     */
    public function addCommentAction($eventid, $teamid, $user)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $event = $this->getRepository('Event')->findOneBy(array('id' => $eventid));
        $team = $this->getRepository('Team')->findOneBy(array('id' => $teamid));

        //$user = $this->getUser();
        $user = $this->getRepository('User')->find($user);

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

    /**
     * @Route("/eventship/add", name="event_eventship_add")
     * @Method({"POST"})
     */
    public function addEventshipAction()
    {
        $request = $this->getRequest();
        $eventId = $request->get('eventid');
        $teamId = $request->get('teamid');
        $eventshipType = $request->get('eventtype', Eventship::TYPE_WEB);

        $user = $this->getUser();
        if (!$user) throw new HttpException(401, 'Must login');
        
        $event = $this->getRepository('Event')->find($eventId);
        $this->securityCheck($event);
        
        $team = $this->getRepository('Team')->find($teamId);
        
        if (!$event) throw new HttpException(404, 'Event not found');
        if (!$team) throw new HttpException(404, 'Team not found');
        
        $manager = $this->get('eventship.manager');
        $manager->addEventship($event, $user, $team, $eventshipType);
        
        return new Response('Added user to event');
    }

    /**
     * @Route("/eventship/remove", name="event_eventship_remove")
     * @Method({"POST"})
     */
    public function removeEventshipAction()
    {
        $request = $this->getRequest();
        $eventId = $request->get('eventid');
        $user = $this->getUser();
        if (!$user) throw new HttpException(401, 'Must login');

        $eventship = $this->getRepository('Eventship')->findBy(array('event' => $eventId, 'author' => $user->getId()));

        if (!$eventship) throw new HttpException(404, 'Eventship not found');
        
        $manager = $this->get('eventship.manager');
        $manager->removeEventship($eventship);
        
        return new Response('Removed user from event');
    }

    /**
     * @Route("/eventship/get", name="event_ajaxeventship")
     */
    public function getAjaxEventship()
    {
        $request = $this->getRequest();
        $eventshipId = $request->get('eventship', false);

        $eventship = $this->getRepository('Eventship')->find($eventshipId);

        return $this->jsonResponse(array(
                    'author' => array(
                        'name' => (string) $eventship->getAuthor(),
                        'image' => $this->getImageUrl($eventship->getAuthor()->getImage(), 'small'),
                        'url' => $this->generateUrl('user_wall', array('username' => $eventship->getAuthor()->getUsername()))
                    ),
                    'team' => array(
                        'id' => $eventship->getTeam()->getId(),
                        'name' => (string) $eventship->getTeam()
                    )
                ));
    }
    
    /**
     * @Route("/test/addeventship", name="event_addeventship")
     */
    
    public function testAddEventship(){
        $request= $this->getRequest();
        $eventId = $request->get('event');
        $authorId = $request->get('author');
        $teamId = $request->get('team');

        $event = $this->getRepository('Event')->find($eventId);
        $author = $this->getRepository('User')->find($authorId);
        $team = $this->getRepository('Team')->find($teamId);
        
        $manager = $this->get('eventship.manager');
        $manager->addEventship($event,  $author, $team, 1);
        
        return $this->jsonResponse(array('ok'));
    }

}
