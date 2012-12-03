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

}
