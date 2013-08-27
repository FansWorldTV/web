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
use Elastica_Search;
use Elastica_Query;
use Elastica_Query_Term;
use Elastica_Facet_Terms;
use Elastica_Type_Mapping;

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
     * @Template()
     */
    public function indexAction()
    {
        $request = $this->getRequest();

        $searchHistoryType = $this->get('fos_elastica.index.website.search_history');
        $userType = $this->get('fos_elastica.index.website.user');
        $idolType = $this->get('fos_elastica.index.website.idol');
        $teamType = $this->get('fos_elastica.index.website.team');
        $photoType = $this->get('fos_elastica.index.website.photo');
        $videoType = $this->get('fos_elastica.index.website.video');

        $searchTerm = trim($request->query->get('query'));

        $em = $this->container->get('sonata.media.entity_manager');

        $log = new SearchHistory();
        $log->setTerm($searchTerm);
        $log->setAuthor($this->getUser());
        $log->setIp($request->getClientIp());
        $log->setDevice('web');
        $em->persist($log);
        $em->flush();

        $client = $this->get('fos_elastica.client');
        $search = new Elastica_Search($client);

        // Configure and execute the search
        $types = array($userType, $idolType, $teamType, $photoType, $videoType);
        //$types = array($searchHistoryType, $userType, $idolType, $teamType);

        $search = $search->addTypes($types);

        /*$index = $this->get('fos_elastica.index');

        $search->addIndex($index);*/

        $resultSet = $search->search('*' . $searchTerm . '*');

        $searchHistoryCount = 0;
        $usersCount = 0;
        $photosCount = 0;
        $videosCount = 0;
        $idolsCount = 0;
        $teamsCount = 0;

        $search_history = array();
        $users = array();
        $photos = array();
        $videos = array();
        $idols = array();
        $teams = array();

        foreach($resultSet as $result){
            $data = $result->getData();
            $type = $result->getType();
            $score = $result->getScore();

            switch ($type) {
                case 'search_history':
                    if ($searchHistoryCount < 3) {
                        $searchHistoryCount++;

                        $search_history[] = array(
                            'value' => $data['term']
                            , 'tokens' => $data['term']
                            , 'type' => $type
                            , 'score' => $score
                        );
                    }

                    break;

                case 'user':
                    $usersCount++;

                    $id = $data['id'];
                    $user = $this->getRepository('User')->find($id);

                    $users[] = $user;

                    /*$image = $this->getImageUrl($user->getImage(), 'small');
                    $url = $this->generateUrl('user_land', array('username' => $user->getUsername()));

                    $users[] = array(
                        'value' => $data['firstName'] . ' ' . $data['lastName']
                        , 'tokens' => $data['username'] . ', ' . $data['firstName'] . ', ' . $data['lastName']
                        , 'type' => $type
                        , 'score' => $score
                        , 'image' => $image
                        , 'url' => $url
                    );*/

                    break;

                case 'photo':
                    $photosCount++;

                    $id = $data['id'];
                    $photo = $this->getRepository('Photo')->find($id);

                    $photos[] = $photo;
                    //$photos[] = array('value' => $data['username'], 'tokens' => $data['username'] . ', ' . $data['firstName'] . ', ' . $data['lastName']);

                    break;

                case 'video':
                    $videosCount++;

                    $id = $data['id'];
                    $video = $this->getRepository('Video')->find($id);

                    $videos[] = $video;

                    //$videos[] = array('value' => $data, 'tokens' => $data);

                    break;

                case 'idol':
                    $idolsCount++;

                    $id =   $data['id'];
                    $idol = $this->getRepository('Idol')->find($id);

                    $idols[] = $idol;

                    /*$image = $this->getImageUrl($idol->getImage(), 'small');
                    $url = $this->generateUrl('idol_land', array('slug' => $idol->getSlug()));

                    $idols[] = array(
                        'value' => $data['firstName'] . ' ' . $data['lastName']
                        , 'tokens' => $data['firstName'] . ', ' . $data['lastName']
                        , 'type' => $type
                        , 'score' => $score
                        , 'image' => $image
                        , 'url' => $url
                    );*/

                    break;

                case 'team':
                    $teamsCount++;

                    $id = $data['id'];
                    $team = $this->getRepository('Team')->find($id);

                    $teams[] = $team;

                    /*$image = $this->getImageUrl($team->getImage(), 'small');
                    $url = $this->generateUrl('team_land', array('slug' => $team->getSlug()));

                    $teams[] = array(
                        'value' => $data['title']
                        , 'tokens' => $data['title'] . ', ' . $data['nicknames']
                        , 'type' => $type
                        , 'score' => $score
                        , 'image' => $image
                        , 'url' => $url
                    );*/

                    break;
            }
        }

        $todo = $usersCount + $photosCount + $videosCount + $idolsCount + $teamsCount;

        if ($idols) {
            $idols = array(
                'ulClass' => 'idols',
                'containerClass' => 'idol-container',
                'list' => $idols
            );
        }

        $fans = array(
            'ulClass' => 'fans',
            'containerClass' => 'fan-container',
            'list' => array()
        );

        if ($users) {
            foreach ($users as $user) {
                $fans['list'][] = $user;
            }
        }

        if ($teams) {
            $teams = array(
                'ulClass' => 'teams',
                'containerClass' => 'team-container',
                'list' => $teams
            );
        }

        $trending = $this->get('tagger')->trending();

        $videosHighlighted = $this->getRepository('Video')->findBy(array('highlight' => true, 'active' => true), array('weight' => 'desc'), 2);

        return array(
            'todoCount' => $todo,
            'videoCount' => $videosCount,
            'idolCount' => $idolsCount,
            'fanCount' => $usersCount,
            'photoCount' => $photosCount,
            'teamCount' => $teamsCount,
            'limit' => array(
                'video' => self::LIMIT_SEARCH_VIDEO,
                'idol' => self::LIMIT_SEARCH_IDOL,
                'fan' => self::LIMIT_SEARCH_USER,
                'photo' => self::LIMIT_SEARCH_PHOTO,
                'team' => self::LIMIT_SEARCH_TEAM
            ),
            'idols' => $idols,
            'photos' => $photos,
            'fans' => $fans,
            'videos' => $videos,
            'teams' => $teams,
            'query' => $searchTerm,
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
                $imageSize = "huge_square";
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


    //public function ajaxSearchAutocomplete3Action(Request $request) {

        /*
        $finder = $this->get('fos_elastica.index.website.user');
        $searchTerm = $request->query->get('q');

        $elasticaQueryString = new Elastica\Query\QueryString($searchTerm);
        $elasticaQuery = new Elastica\Query();
        $elasticaQuery->setQuery($elasticaQueryString);

        $facets = array();
        $elasticaFacet = new \Elastica\Facet\Terms('term');
        $elasticaFacet->setField('term');
        $elasticaFacet->setSize(10);
        $facets[] = $elasticaFacet;

        $elasticaQuery->setFacets($facets);

        $resultSet = $finder->search($searchTerm);

        $response = array();

        foreach($resultSet as $result){
            $data = $result->getData();
            $response[] = array('value' => $data['username'], 'tokens' => $data['username'] . ', ' . $data['firstName'] . ', ' . $data['lastName']);
        }

        return $this->jsonResponse($response);

*/
        /*$query = new Elastica_Query;

        // Create the facet
        $facet = new Elastica_Facet_Terms('term');
        $facet->setField('term')
              ->setAllTerms(true)
              ->setSize(200);

        // Add facet to "global" query
        $query->addFacet($facet);



        $client = $this->get('fos_elastica.client');
        $search = new Elastica_Search($client);

        $search_history = $this->get('fos_elastica.index.website.search_history');

        $types = array($search_history);
        $search->addTypes($types);

        $index = $this->get('fos_elastica.index');

        $search->addIndex($index);

        $resultSet = $search->search($query);  // Configure and execute the search

        //return $this->jsonResponse($query->toArray());

        $response = array();

        foreach($resultSet as $result){
            $data = $result->getData();
            $explanation = $result->getExplanation();
            $response[] = array('value' => $data['term'], 'tokens' => $data['term']); //, 'count' => $data['count']
        }

        return $this->jsonResponse($response);
    }*/


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