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
use Dodici\Fansworld\WebBundle\Model\UserRepository;

/**
 * My things controller controller.
 * @Route("/my")
 */
class ThingsController extends SiteController
{

    const IDOLS_LIMIT = null;
    const TEAMS_LIMIT = null;
    const ALBUMS_LIMIT = 6;
    const PHOTOS_LIMIT = 15;
    const FANS_LIMIT = 15;

    /**
     * My Idols
     * 
     * @Route("/idols", name="things_idols")
     * @Template()
     * @Secure(roles="ROLE_USER")
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
     * My fans
     * @Route("/fans", name="things_fans")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function fansAction()
    {
        $user = $this->getUser();
        $fans = $this->getRepository('User')->fans($user, true, self::FANS_LIMIT);
        $countFans = $this->getRepository('User')->countFans($user, true);
        $lastVideos = $this->getRepository('Video')->findBy(array('highlight' => true), array('createdAt' => 'desc'), 4);

        return array(
            'fans' => $fans,
            'addMore' => ( 1 * self::FANS_LIMIT) < $countFans ? true : false,
            'lastVideos' => $lastVideos
        );
    }

    /**
     * @Route("/fans/ajax", name="things_ajaxfans")
     * @Secure(roles="ROLE_USER")
     */
    public function ajaxFansAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $serializer = $this->get('serializer');

        $direction = $request->get('direction', 0);
        $filter = $request->get('filter', 0);
        $page = $request->get('page', 1);

        $page = (int) $page;
        $direction = (int) $direction;
        $filter = (int) $filter;

        $offset = ($page - 1) * self::FANS_LIMIT;

        $direction = $direction == 0 ? true: false;
        $userRepo = $this->getRepository('User');
        $userRepo instanceof UserRepository;


        switch ($filter) {
            case 0:
                $fans = $userRepo->fans($user, $direction, self::FANS_LIMIT, $offset);
                $countFans = $userRepo->countFans($user, $direction);
                break;
            case 1:
                $fans = $userRepo->fansNearby($user, $direction, self::FANS_LIMIT, $offset);
                $countFans = $userRepo->countFansNearby($user, $direction);
                break;
            case 2:
                $fans = $userRepo->fansSameFavoriteTeam($user, $direction, self::FANS_LIMIT, $offset);
                $countFans = $userRepo->countFansSameFavoriteTeam($user, $direction);
                break;
            case 3:
                $fans = $userRepo->fansMostSimilar($user, $direction, self::FANS_LIMIT, $offset);
                $countFans = $userRepo->countFansMostSimilar($user, $direction);
                break;
        }
        
        $addMore = ( self::FANS_LIMIT * $page ) < $countFans ? true : false;
        
        $response = array(
            'fans' => array(),
            'addMore' => $addMore
        );
        
        foreach($fans as $fan){
            $serialized = $serializer->values($fan, 'big_square');
            array_push($response['fans'], $serialized);
        }
        
        return $this->jsonResponse($response);
    }

    /**
     *  My Videos
     * @Route("/videos", name="things_videos")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function videosAction()
    {
        $user = $this->getUser();
        $videos = $this->getRepository('Video')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'desc'));
        $lastVideos = $this->getRepository('Video')->findBy(array('highlight' => true), array('createdAt' => 'desc'), 4);

        return array(
            'user' => $user,
            'videos' => $videos,
            'selfWall' => true,
            'lastVideos' => $lastVideos
        );
    }

    /**
     * Videos filters
     * @Route("/videos/filter/ajax", name="things_videosajax")
     * @Secure(roles="ROLE_USER")
     */
    public function videosAjaxAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $type = $request->get('type', 0);
        $type = (int) $type;
        $serializer = $this->get('serializer');

        $response = array(
            'videos' => array(),
            'error' => false
        );

        switch ($type) {
            case 0:
                $videos = $this->getRepository('Video')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'desc'));
                break;

            case 1:
                $videos = $this->getRepository('Video')->commonIdols($user);
                break;

            case 2:
                $videos = $this->getRepository('Video')->commonTeams($user);
                break;

            case 3:
                $videos = $this->getRepository('Video')->commonCategories($user);
                break;

            case 4:
                $playlist = $this->get('video.playlist');
                $videos = $playlist->get($user);
                break;
        }

        $response['videos'] = $serializer->values($videos, 'medium');

        return $this->jsonResponse($response);
    }

    /**
     * My Photos
     * @Route("/photos", name="things_photos")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function photosAction()
    {
        $user = $this->getUser();

        $photos = $this->getRepository('Photo')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::PHOTOS_LIMIT);
        $albums = $this->getRepository('Album')->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::ALBUMS_LIMIT);

        $photosTotalCount = $this->getRepository('Photo')->countBy(array('author' => $user->getId(), 'active' => true));
        $albumsTotalCount = $this->getRepository('Album')->countBy(array('author' => $user->getId(), 'active' => true));

        $viewMorePhotos = $photosTotalCount > self::PHOTOS_LIMIT ? true : false;
        $viewMoreAlbums = $albumsTotalCount > self::ALBUMS_LIMIT ? true : false;

        $lastVideos = $this->getRepository('Video')->findBy(array('highlight' => true), array('createdAt' => 'desc'), 4);

        return array(
            'user' => $user,
            'lastVideos' => $lastVideos,
            'photos' => $photos,
            'albums' => $albums,
            'photosTotalCount' => $photosTotalCount,
            'albumsTotalCount' => $albumsTotalCount,
            'viewMorePhotos' => $viewMorePhotos,
            'viewMoreAlbums' => $viewMoreAlbums
        );
    }

    /**
     * Photos filter
     * @Route("/photos/filter/ajax", name="things_photosajax")
     * @Secure(roles="ROLE_USER")
     */
    public function photosAjaxAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $serializer = $this->get('serializer');

        $page = $request->get('page', 1);
        $offsetAlbums = ($page - 1) * self::ALBUMS_LIMIT;
        $offsetPhotos = ($page - 1) * self::PHOTOS_LIMIT;

        $type = $request->get('type', 0);
        $type = (int) $type;

        $photoRepo = $this->getRepository('Photo');
        $albumRepo = $this->getRepository('Album');

        $response = array(
            'photos' => array(),
            'albums' => array(),
            'viewMorePhotos' => null,
            'viewMoreAlbums' => null
        );

        $albums = null;
        $albumsTotalCount = 0;

        switch ($type) {
            case 0:
                $photos = $photoRepo->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::PHOTOS_LIMIT, $offsetPhotos);
                $albums = $albumRepo->findBy(array('author' => $user->getId(), 'active' => true), array('createdAt' => 'DESC'), self::ALBUMS_LIMIT, $offsetAlbums);

                $photosTotalCount = $photoRepo->countBy(array('author' => $user->getId(), 'active' => true));
                $albumsTotalCount = $albumRepo->countBy(array('author' => $user->getId(), 'active' => true));

                $photos = $serializer->values($photos, 'small');
                break;
            case 1:
                $photos = $photoRepo->userTagged($user, self::PHOTOS_LIMIT, $offsetPhotos);
                $photosTotalCount = $photoRepo->countUserTagged($user);
                $photos = $serializer->values($photos, 'medium');
                break;
            case 2:
                $photos = $photoRepo->userLiked($user, self::PHOTOS_LIMIT, $offsetPhotos);
                $photosTotalCount = $photoRepo->countUserLiked($user);
                $photos = $serializer->values($photos, 'medium');
                break;
        }

        if ($photosTotalCount == 0) {
            $viewMorePhotos = false;
        } else {
            $viewMorePhotos = ( $page * self::PHOTOS_LIMIT ) < $photosTotalCount ? true : false;
        }

        if ($albumsTotalCount == 0) {
            $viewMoreAlbums = false;
        } else {
            $viewMoreAlbums = ( $page * self::ALBUMS_LIMIT ) < $albumsTotalCount ? true : false;
        }

        return $this->jsonResponse(array(
                    'albums' => $serializer->values($albums, 'medium'),
                    'photos' => $photos,
                    'viewMorePhotos' => $viewMorePhotos,
                    'viewMoreAlbums' => $viewMoreAlbums,
                    'photosTotalCount' => $photosTotalCount
                ));
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
                foreach ($events as $event) {
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
