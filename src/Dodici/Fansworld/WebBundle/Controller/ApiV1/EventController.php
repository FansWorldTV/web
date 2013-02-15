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
     * [signed if user_id given] Event - list
     *
     * @Route("/events", name="api_v1_event_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> user_id: int
     * - <required if user_id given> [user token]
     * - <optional> date_from: int (ts UTC)
     * - <optional> date_to: int (ts UTC)
     * - <optional> sport: int (sport id to filter by)
     * - <optional> teamcategory: int (teamcategory id to filter by)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'isfan'(user_id required)|'popular'|'upcoming' (default: popular)
     * - <optional> imageformat: string
     * - [signature params if user_id given]
     *
     * @return
     * array(
     * 		@see self::showAction
     * 		...
     * )
     */
    public function listAction()
    {
        try {
            //if (!$id) throw new HttpException(400, 'Invalid id');
            //if (!$events || $events) throw new HttpException(404, 'Event not found');
            //if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');

                //$user = $this->checkUserToken($userid, $request->get('user_token'));

                $datefrom = $request->get('date_from');
                $dateto = $request->get('date_to');
                $teamcategory = $request->get('teamcategory');
                $limit = $request->get('limit');
                $offset = $request->get('offset');
                $sort = $request->get('sort'); //  WIP
                $imageformat = $request->get('imageformat'); // WIP
                $sport = $request->get('sport'); // ??

                if (!$userid) {
                    $events = $this->getRepository('Event')->findAll();
                } else {
                    $user = $this->getRepository('User')->find($userid);
                    $events = $this->getRepository('Event')->checkedInto($user, null, $limit, $datefrom, $dateto, $teamcategory, $offset);
                }

                $result = $this->get('serializer')->values($events);
                return $this->result($result);
            //} else {
                //throw new HttpException(401, 'Invalid signature');
            //}
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
        // return $this->result(null);
    }

	/**
     * [signed] Event - wall
     *
     * @Route("/event/{id}/wall", name="api_v1_event_wall", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
	 * - user_id: int
	 * - [user_token]
	 * - date_from: int (ts UTC)
	 * - date_to: int (ts UTC)
     * - <optional> imageformat: string
     * - [signature params]
     *
     * @return
     * array(
     * 		(TODO)
     * 		...
     * )
     */
    public function wallAction($id)
    {
        // TODO
        // Throw exception if the user isn't checked into the event
        // Comment, EventTweet, and EventIncident repos have an eventWall method
        // return $this->result(null);
        try {
             //if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');

                //$user = $this->checkUserToken($userid, $request->get('user_token'));

                if (!$id) throw new HttpException(400, 'Invalid id');
                $event = $this->getRepository('Event')->find($id);
                if (!$event || ($event && !$event->getActive())) throw new HttpException(404, 'Event not found');

                // Verify if user checked into the event
                $eventship = $this->getRepository('Eventship')->findOneBy(array('author' => $userid, 'event' => $id));
                if (!$eventship) throw new HttpException(401, 'User has not checked into event');

                $datefrom = $request->get('date_from');
                $dateto = $request->get('date_to');

                $response['comments'] = array();
                $response['incidents'] = array();
                $response['tweets'] = array();

                $eventComments = $this->getRepository('Comment')->eventWall($event, $datefrom, $dateto);
                foreach ($eventComments as $comment) {
                    array_push($response['comments'], array('id' => $comment->getId(), 'teamid' => $comment->getTeam()->getId()));
                }

                $eventTweets = $this->getRepository('EventTweet')->eventWall($event, $datefrom, $dateto);
                foreach ($eventTweets as $tweet) {
                    array_push($response['tweets'], array('id' => $tweet->getId(), 'teamid' => $tweet->getTeam()->getId()));
                }

                $eventIncidents = $this->getRepository('EventIncident')->eventWall($event, $datefrom, $dateto);
                foreach ($eventIncidents as $incident) {
                    array_push($response['incidents'], array('id' => $incident->getId(), 'teamid' => $incident->getTeam()->getId()));
                }

                $response['event'] = $this->get('serializer')->values($event);
                return $this->result($response);
            //} else {
                //throw new HttpException(401, 'Invalid signature');
            //}
        } catch (\Exception $e) {
            return $this->plainException($e);
        }

    }

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
     *                  text: string
     *                  date: date ('d-m-Y')
     * 			showdate: date ('d-m-Y')
     *                  url: string
     *                  started: boolean
     *                  checked: boolean
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