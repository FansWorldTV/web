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

    /**
     * @Route("/{id}/{slug}", name= "event_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     */
    public function showAction($id)
    {
        //TODO: todo
        $event = $this->getRepository('Event')->find($id);

        $this->securityCheck($event);

        return new Response('TODO');
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
            array_push($teams, array($team->getId() => (string) $team));
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
