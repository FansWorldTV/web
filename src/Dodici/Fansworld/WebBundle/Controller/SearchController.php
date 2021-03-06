<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Services\Search;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\SearchHistory;

class SearchController extends SiteController
{

    const LIMIT_SEARCH_VIDEO = 14;
    const LIMIT_SEARCH_IDOL = 9;
    const LIMIT_SEARCH_USER = 9;
    const LIMIT_SEARCH_PHOTO = 10;
    const LIMIT_SEARCH_EVENT = 4;
    const LIMIT_SEARCH_TEAM = 4;

    /**
     * @Route("/search", name = "search_home")
     * @Secure(roles="ROLE_USER")
     * @Template()
     */
    public function indexAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $query = $request->get('query', null);
        $ip = $this->getRequest()->server->get("REMOTE_ADDR");

        // Log search
        $searchLog = $this->get('search')->log($query, $user, $ip, 'web');

        $videoRepo = $this->getRepository('Video');
        $idolRepo = $this->getRepository('Idol');
        $fanRepo = $this->getRepository('User');
        $photoRepo = $this->getRepository('Photo');
        $eventRepo = $this->getRepository('Event');
        $teamRepo = $this->getRepository('Team');

        $videoSearch = $videoRepo->search($query, $user, self::LIMIT_SEARCH_VIDEO);
        $videoCount = $videoRepo->countSearch($query);

        $idolSearch = $idolRepo->search($query, null, self::LIMIT_SEARCH_IDOL);
        $idolCount = $idolRepo->countSearch($query);

        $fanSearch = $fanRepo->search($query, null, self::LIMIT_SEARCH_USER);
        $fanCount = $fanRepo->countSearch($query);

        $photoSearch = $photoRepo->search($query, null, self::LIMIT_SEARCH_PHOTO);
        $photoCount = $photoRepo->countSearch($query);

        $eventSearch = $eventRepo->search($query, null, self::LIMIT_SEARCH_EVENT);
        $eventCount = $eventRepo->countSearch($query);

        $teamSearch = $teamRepo->search($query, null, self::LIMIT_SEARCH_TEAM);
        $teamCount = $teamRepo->countSearch($query);

        $todo = $videoCount + $idolCount + $fanCount + $photoCount + $eventCount;

        $idols = array(
            'ulClass' => 'idols',
            'containerClass' => 'idol-container',
            'list' => $idolSearch
        );

        $fans = array(
            'ulClass' => 'fans',
            'containerClass' => 'fan-container',
            'list' => array()
        );

        $teams = array(
            'ulClass' => 'teams',
            'containerClass' => 'team-container',
            'list' => $teamSearch
        );

        foreach ($fanSearch as $fan) {
            $fans['list'][] = $fan[0];
        }

        $trending = $this->get('tagger')->trending();

        $videosHighlighted = $this->getRepository('Video')->findBy(array('highlight' => true, 'active' => true), array('weight' => 'desc'), 2);

        return array(
            'todoCount' => $todo,
            'videoCount' => $videoCount,
            'idolCount' => $idolCount,
            'fanCount' => $fanCount,
            'photoCount' => $photoCount,
            /*'eventCount' => $eventCount,*/
            'teamCount' => $teamCount,
            'limit' => array(
                'video' => self::LIMIT_SEARCH_VIDEO,
                'idol' => self::LIMIT_SEARCH_IDOL,
                'fan' => self::LIMIT_SEARCH_USER,
                'photo' => self::LIMIT_SEARCH_PHOTO,
                'team' => self::LIMIT_SEARCH_TEAM
            ),
            'idols' => $idols,
            /*'events' => $eventSearch,*/
            'photos' => $photoSearch,
            'fans' => $fans,
            'videos' => $videoSearch,
            'teams' => $teams,
            'query' => $query,
            'trending' => $trending,
            'videosHighlighted' => $videosHighlighted
        );
    }

    /**
     *  @Route("/ajax/search", name="search_ajaxsearch")
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();

        $query = $request->get('query', null);
        $page = $request->get('page', 1);
        $type = $request->get('type', false);

        if ($type) {
            $limit = constant('self::LIMIT_SEARCH_' . strtoupper($type));
        } else {
            $limit = null;
        }

        $page = ((int) $page );
        $offset = ( $page - 1 ) * $limit;

        $response = array();

        $searcher = $this->get('search');
        $searcher instanceof Search;

        $searchType = constant('Dodici\Fansworld\WebBundle\Services\Search::' . strtoupper('TYPE_' . $type));

        $search = $searcher->search($query, $searchType, null, $limit, $offset);

        $countAll = $searcher->count($query, $searchType);

        $serializer = $this->get('serializer');

        switch ($type) {
            case 'video':
                $imageSize = "home_video";
                break;
            default :
                $imageSize = "medium";
                break;
        }

        $response['search'] = $serializer->values($search, $imageSize);

        if ($response['search']) {
            foreach ($response['search'] as $key => $el) {
                if(!isset($el['id'])){
                    continue;
                }
                
                if (array_key_exists('duration', $el)) {
                    $response['search'][$key]['duration'] = $el['duration'];
                }

                $entity = $this->getRepository(ucfirst($type))->find($el['id']);

                switch ($type) {
                    case 'event':
                        if ($this->getUser() instanceof User) {
                            $response['search'][$key]['checked'] = $this->getRepository('Eventship')->findOneBy(array('author' => $this->getUser()->getId(), 'event' => $el['id'])) ? true : false;
                        } else {
                            $response['search'][$key]['checked'] = null;
                        }
                        $now = new \DateTime();
                        $started = ($entity->getFromtime() <= $now);

                        $response['search'][$key]['text'] = $this->get('appstate')->getEventText($el['id']);
                        $response['search'][$key]['date'] = $entity->getFromtime()->format('d-m-Y');
                        $response['search'][$key]['showdate'] = $entity->getFromtime()->format('d/m/Y H:i');
                        $response['search'][$key]['url'] = $this->generateUrl('event_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()));
                        $response['search'][$key]['started'] = $started;

                        break;

                    case 'video':
                        $response['search'][$key]['url'] = $this->generateUrl('video_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()));
                        break;

                    case 'idol':
                        $response['search'][$key]['url'] = $this->generateUrl('idol_land', array('slug' => $entity->getSlug()));
                        break;

                    case 'user':
                        $response['search'][$key]['url'] = $this->generateUrl('user_land', array('slug' => $entity->getUsername()));
                        break;

                    case 'photo':
                        $response['search'][$key]['url'] = $this->generateUrl('photo_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()));
                        break;

                    case 'team':
                        $response['search'][$key]['url'] = $this->generateUrl('team_land', array('slug' => $entity->getSlug()));
                        break;

                }
            }
        }

        $response['addMore'] = $countAll > ($limit * $page) ? true : false;

        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/search/autocomplete", name="search_ajaxsearch_autocomplete")
     */
    public function ajaxSearchAutocompleteAction(Request $request) {
        $request = $this->getRequest();

        $searchHistoryType = $this->get('fos_elastica.index.website.search_history');
        $userType = $this->get('fos_elastica.index.website.user');
        $idolType = $this->get('fos_elastica.index.website.idol');
        $teamType = $this->get('fos_elastica.index.website.team');

        $searchTerm = trim($request->query->get('q'));

        $em = $this->container->get('sonata.media.entity_manager');

        /*$log = new SearchHistory();
        $log->setTerm($searchTerm);
        $log->setAuthor($this->getUser());
        $log->setIp($request->getClientIp());
        $log->setDevice('web');
        $em->persist($log);
        $em->flush();
        */

        $client = $this->get('fos_elastica.client');
        $search = new Elastica_Search($client);

        // Configure and execute the search
        $types = array($userType, $idolType, $teamType);
        //$types = array($searchHistoryType, $userType, $idolType, $teamType);

        $search = $search->addTypes($types);

        /*$index = $this->get('fos_elastica.index');

        $search->addIndex($index);*/

        $resultSet = $search->search('*' . $searchTerm . '*');

        $response = array();

        $searchHistoryCount = 0;

        foreach($resultSet as $result){
            $data = $result->getData();
            $type = $result->getType();
            $score = $result->getScore();

            switch ($type) {
                case 'search_history':
                    if ($searchHistoryCount < 3) {
                        $searchHistoryCount++;

                        $response['search_history'][] = array(
                            'value' => $data['term']
                        , 'tokens' => $data['term']
                        , 'type' => $type
                        , 'score' => $score
                        );
                    }

                    break;

                case 'user':
                    $id = $data['id'];
                    $user = $this->getRepository('User')->find($id);

                    $image = $this->getImageUrl($user->getImage(), 'small');
                    $url = $this->generateUrl('user_land', array('username' => $user->getUsername()));

                    $response['suggestions'][] = array(
                        'value' => $data['firstName'] . ' ' . $data['lastName']
                    , 'tokens' => $data['username'] . ', ' . $data['firstName'] . ', ' . $data['lastName']
                    , 'type' => $type
                    , 'score' => $score
                    , 'image' => $image
                    , 'url' => $url
                    );

                    break;

                //case 'photo':
                //    $response[] = array('value' => $data['username'], 'tokens' => $data['username'] . ', ' . $data['firstName'] . ', ' . $data['lastName']);
                //    break;
                //case 'video':
                //    $response[] = array('value' => $data, 'tokens' => $data);
                //    break;

                case 'idol':
                    $id =   $data['id'];
                    $idol = $this->getRepository('idol')->find($id);

                    $image = $this->getImageUrl($idol->getImage(), 'small');
                    $url = $this->generateUrl('idol_land', array('slug' => $idol->getSlug()));

                    $response['suggestions'][] = array(
                        'value' => $data['firstName'] . ' ' . $data['lastName']
                    , 'tokens' => $data['firstName'] . ', ' . $data['lastName']
                    , 'type' => $type
                    , 'score' => $score
                    , 'image' => $image
                    , 'url' => $url
                    );

                    break;

                case 'team':
                    $id = $data['id'];
                    $team = $this->getRepository('team')->find($id);

                    $image = $this->getImageUrl($team->getImage(), 'small');
                    $url = $this->generateUrl('team_land', array('slug' => $team->getSlug()));

                    $response['suggestions'][] = array(
                        'value' => $data['title']
                    , 'tokens' => $data['title'] . ', ' . $data['nicknames']
                    , 'type' => $type
                    , 'score' => $score
                    , 'image' => $image
                    , 'url' => $url
                    );

                    break;
            }
        }

        //$response['search_history_count'] = $searchHistoryCount;

        return $this->jsonResponse($response);
    }
}
