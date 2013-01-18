<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Eventship;
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
 * API controller - Events
 * V1
 * @Route("/api_v1")
 */
class EventController extends BaseController
{
	
	/**
     * Event - show
     * 
     * @Route("/event/{id}", name="api_v1_event_show", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params: none
     * 
     * @return 
     * array (
     * 			id: int,
     * 			title: string,
     * 			slug: string,
     * 			createdAt: int (timestamp UTC),
     * 			stadium: string,
     * 			finished: boolean,
     * 			
     * 			teams: array (
     * 				array(
     * 					id: int,
	 *					title: string,
	 *					fanCount: int,
	 *					image: array(id: int, url: string)
     *     			),
     *     			...
     * 			)
     * 		)
     * 
     */
	public function showAction($id)
	{
	    try {
    	    if (!$id) throw new HttpException(400, 'Invalid id');
            $event = $this->getRepository('Event')->find($id);
            if (!$event || ($event && !$event->getActive())) throw new HttpException(404, 'Event not found');
            
            $result = $this->get('serializer')->values($event);
            
            return $this->result($result);
	    } catch (\Exception $e) {
            return $this->plainException($e);
        }
	}
	
	/**
     * [signed] Event check in
     * 
     * @Route("/event/checkin", name="api_v1_event_checkin")
     * @Method({"POST"})
     *
     * Post params:
	 * - user_id: int
	 * - event_id: int
	 * - team_id: int
	 * - checkin_type: 'tv'|'radio'|'web'|'live'
	 * - [user token]
     * - [signature params]
     * 
     */
    public function checkinAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $types = Eventship::getTypes();
                $typemap = array_flip($types);
                
                $eventid = $request->get('event_id');
                if (!$eventid) throw new HttpException(400, 'Invalid event_id');
                $teamid = $request->get('team_id');
                if (!$teamid) throw new HttpException(400, 'Invalid team_id');
                $type = $request->get('checkin_type');
                if (!in_array($type, $typemap)) throw new HttpException(400, 'Invalid checkin_type');
                
                $event = $this->getRepository('Event')->find($eventid);
                if (!$event || ($event && !$event->getActive())) throw new HttpException(404, 'Event not found');
                if ($event->getFinished()) throw new HttpException(401, 'Event already finished');
                $realtype = $typemap[$type];
                
                $teams = array();
                $hasteams = $event->getHasteams();
                foreach ($hasteams as $ht) $teams[$ht->getTeam()->getId()] = $ht->getTeam();
                if (!in_array($teamid, array_keys($teams))) throw new HttpException(401, 'Selected team is not in event');
                $team = $teams[$teamid];
                
                $this->get('eventship.manager')->addEventship($event, $user, $team, $realtype);
                
                return $this->result(true);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
