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
use Dodici\Fansworld\WebBundle\Entity\Eventship;

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

}
