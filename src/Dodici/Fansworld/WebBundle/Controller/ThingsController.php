<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * My things controller controller.
 * @Route("/my")
 */
class ThingsController extends SiteController
{

    const IDOLS_LIMIT = null;
    const TEAMS_LIMIT = null;

    /**
     * My Idols
     * 
     * @Route("/idols", name="things_idols")
     * @Template()
     */
    public function idolsAction()
    {
        $user = $this->getUser();
        $idolshipRepo = $this->getRepository('Idolship');
        $idolships = $idolshipRepo->findBy(array('author' => $user->getId()), array('favorite' => 'desc', 'score' => 'desc', 'createdAt' => 'desc'), self::IDOLS_LIMIT);

        $idols = array();
        foreach ($idolships as $idolship) {
            array_push($idols, $idolship->getIdol());
        }

        $ranking = array();
        $idolsRank = $this->getRepository('Idol')->findBy(array(), array('fanCount' => 'desc'), 10);
        foreach ($idolsRank as $idol) {
            array_push($ranking, $idol);
        }

        $lastVideos = $this->getRepository('Video')->commonIdols($user, 4);

        return array(
            'user' => $user,
            'idols' => $idols,
            'selfWall' => true,
            'ranking' => $ranking,
            'lastVideos' => $lastVideos
        );
    }

    /**
     * My teams
     * 
     * @Route("/teams", name="things_teams")
     * @Template()
     */
    public function teamsAction()
    {
        $user = $this->getUser();
        $teamRepo = $this->getRepository('Team');
        $teamshipRepo = $this->getRepository('Teamship');
        $teamships = $teamshipRepo->findBy(array('author' => $user->getId()), array('favorite' => 'desc', 'score' => 'desc', 'createdAt' => 'desc'), self::TEAMS_LIMIT);

        $teams = array();
        foreach ($teamships as $teamship) {
            array_push($teams, $teamship->getTeam());
        }

        $ranking = array();
        $teamsRank = $teamRepo->findBy(array(), array('fanCount' => 'desc'), 10);
        foreach ($teamsRank as $team) {
            array_push($ranking, $team);
        }

        $lastTeamsSearch = $this->getRepository('Video')->commonTeams($user, 4);


        return array(
            'user' => $user,
            'teams' => $teams,
            'selfWall' => true,
            'lastTeams' => $lastTeamsSearch,
            'teamsRank' => $ranking
        );
    }

    /**
     * My matchs
     * @Route("/matchs", name="things_matchs")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function matchsAction()
    {
        $user = $this->getUser();
        $eventships = $this->getRepository('Eventship')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'));

        $events = array();
        foreach ($eventships as $eventship) {
            if (!$eventship->getEvent()->getFinished()) {
                array_push($events, $eventship->getEvent());
            }
        }

        $ranking = array();
        $teamsRank = $this->getRepository('Team')->findBy(array(), array('fanCount' => 'desc'), 10);
        foreach ($teamsRank as $team) {
            array_push($ranking, $team);
        }

        $lastTeamsSearch = $this->getRepository('Video')->commonTeams($user, 4);

        return array(
            'user' => $user,
            'events' => $events,
            'selfWall' => true,
            'lastTeams' => $lastTeamsSearch,
            'teamsRank' => $ranking
        );
    }

    /**
     * filter matchs
     * @Route("/matchs/ajax", name="things_ajaxmatchs")
     * @Secure(roles="ROLE_USER")
     */
    public function matchsAjaxAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $type = $request->get('type', 0);
        $response = array(
            'events' => array()
        );

        switch ($type) {
            case 0:
                $eventships = $this->getRepository('Eventship')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'));
                foreach ($eventships as $eventship) {
                    if (!$eventship->getEvent()->getFinished()) {
                        array_push($response['events'], $this->serializeEvent($eventship->getEvent()));
                    }
                }
                break;
            case 1:
                $events = $this->getRepository('Event')->commonTeams($user, null, false);
                foreach ($events as $event){
                    array_push($response['events'], $this->serializeEvent($event));
                }
                break;
            case 2:
                $eventships = $this->getRepository('Eventship')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'));
                foreach ($eventships as $eventship) {
                    if ($eventship->getEvent()->getFinished()) {
                        array_push($response['events'], $this->serializeEvent($eventship->getEvent()));
                    }
                }
                break;
        }

        return $this->jsonResponse($response);
    }

    private function serializeEvent($event)
    {
        $now = new \DateTime();
        $teams = array();
        foreach ($event->getHasteams() as $hasTeam) {
            $team = $hasTeam->getTeam();
            $teams[] = array(
                'title' => (string) $team,
                'image' => $this->getImageUrl($team->getImage(), 'mini_square'),
                'score' => $hasTeam->getScore()
            );
        }

        $started = ($event->getFromtime() <= $now);

        if ($this->getUser() instanceof User) {
            $checked = $this->getRepository('Eventship')->findOneBy(array('author' => $this->getUser()->getId(), 'event' => $event->getId())) ? true : false;
        } else {
            $checked = null;
        }

        return array(
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

}
