<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Event;
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
use Dodici\Fansworld\WebBundle\Services\Search;

/**
 * API controller - Search
 * V1
 * @Route("/api_v1")
 */
class SearchController extends BaseController
{
	/**
     * Popular history
     *
     * @Route("/search/history", name="api_v1_search_history")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> match: string (partial match)
	 * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     *
     * @return
     * array (
     * 		array(
     * 			term: string,
     * 			cnt: int
     * 		),
     * 		...
     * )
     */
    public function historyAction()
    {
        try {
            $request = $this->getRequest();
            $match = $request->get('match');
            $pagination = $this->pagination();
            $pagination['sort_order'] = null;
            $pagination['sort'] = null;

            $terms = $this->get('search')->topTerms(
                $match,
                null, null, null,
                $pagination['limit'],
                $pagination['offset']
            );
            $return = $this->get('serializer')->values($terms);

            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

	/**
     * [signed if user_id given] Search - search by term
     *
     * @Route("/search/term", name="api_v1_search_term")
     * @Method({"GET"})
     *
     * Get params:
     * - term: string
     * - <optional> result_types: comma-separated string; possible values: video, photo, event, user, team, idol
     * - <optional> user_id: int, will be used for filtering
     * - <required if user_id given> user_token: string, user token
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
	 * - <optional> imageformat: string
     * - [signature params if user_id given]
     *
     * @return
     * array (
     *      <resulttype> : array (
     *          array (
     *              id: int,
     *              title: string,
     *              <if applicable> image: array(id: int, url: string),
     *              <if applicable> author: @see SecurityController::loginAction() - without token,
     *              createdAt: int (timestamp UTC),
     *              <if event> teams: @see EventController::showAction(),
     *              <if event> showdate: @see EventController::showAction(),
     *              <if user> username: @see UserController:showAction(),
     *              <if user|idol> firstname: string,
     *              <if user|idol> lastname: string,
     *              <if video> category_id: int,
     *              <if video> provider: string,
     *
     *              // extra fields
     *              <video|photo|team|idol> content: string,
     *              <video|photo|team|idol> visitCount: int,
     *              <user|team|idol> splash: array(id: int, url: string),
     *              <user|team|idol> photoCount: int,
     *              <user|team|idol> videoCount: int,
     *              <user|team|idol> fanCount: int,
     *              <video|photo> likeCount: int,
     *              <video|photo> commentCount: int,
     *              <video|photo> url: string,
     *              <video|photo> liked: boolean,
     *              <video> watchlisted: boolean,
     *              <video> duration: int (seconds),
     *              <video|photo> author: serializer of (user),
     *              <photo> album: array (
     *                  id: int,
     *                  title: string,
     *                  photoCount: int
     *              )
     *          ),
     *          ...
     *
     *      ),
     *      ...
     *      )
     *
     */
    public function termAction()
    {
        try {
            $request = $this->getRequest();

            $term = $request->get('term');
            if (!$term) throw new HttpException(400, 'Requires term');

            $userid = $request->get('user_id');
            $rts = array('video','photo','event','user','team','idol');
            $resulttypes = $request->get('result_types');
            if ($resulttypes) {
                $exp = explode(',', $resulttypes);
                $searchtypes = array();
                foreach ($exp as $x) {
                    if ($x && in_array($x, $rts)) {
                        if (in_array($x, $searchtypes)) throw new HttpException(400, 'Duplicate extra field: "'.$x.'"');
                        $searchtypes[] = $x;
                    } else {
                        throw new HttpException(400, 'Invalid result type: "'.$x.'"');
                    }
                }
            } else {
                $searchtypes = $rts;
            }

            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }

            $pagination = $this->pagination();
            $pagination['sort_order'] = null;
            $pagination['sort'] = null;

            /* here goes the search */
            $return = array();
            $search = $this->get('search');

            // Log search
            $searchLog = $search->log($term, $user, null, 'api');

            $highestcount = 0;
            foreach ($searchtypes as $type) {
                $items = $search->search($term, $type, $user, $pagination['limit'], $pagination['offset']);
                $return[$type] = array();
                foreach ($items as $i) {
                    if ($type == 'user') $i = $i[0];
                    $data = array(
                        'id' => $i->getId(),
                        'title' => (string)$i,
                        'createdAt' => ($i->getCreatedAt() ? $i->getCreatedAt()->format('U') : null),
                        'type'=> $type
                    );

                    if (method_exists($i, 'getImage')) $data['image'] = $this->imageValues($i->getImage());

                    if ($i instanceof Event) {
                        $data['showdate'] = ($i->getFromtime() ? $i->getFromtime()->format('U') : null);
                        foreach($i->getHasTeams() as $ht){
                            $data['teams'][] = $this->get('serializer')->values($ht->getTeam());
                        }
                    }

                    $allowedfields = array(
                        'content', 'visitCount', 'likeCount', 'commentCount', 'liked', 'url', 'watchlisted',
                            'duration', 'album', 'photoCount', 'videoCount', 'fanCount', 'splash', 'author'
                    );
                    $extrafields = $this->getExtraFields($allowedfields);

                    if ($this->_evaluateConditions($type, array('video', 'photo', 'team', 'idol'))) {
                        if (in_array('content', $extrafields)) $data['content'] = $i->getContent();
                        if (in_array('visitCount', $extrafields)) $data['visitCount'] = $i->getVisitCount();

                        if ($this->_evaluateConditions($type, array('video', 'photo'))) {
                            if (in_array('likeCount', $extrafields)) $data['likeCount'] = $i->getLikeCount();
                            if (in_array('commentCount', $extrafields)) $data['commentCount'] =  $i->getCommentCount();
                            if (in_array('liked', $extrafields)) $data['liked'] = $this->get('liker')->isLiking($i, $user) ? true : false;
                            if (in_array('url', $extrafields)) $data['url'] =  $this->get('router')->generate($type.'_show', array('id' => $i->getId(), 'slug' => $i->getSlug()), true);
                            if (in_array('author', $extrafields)) $data['author'] = ($i->getAuthor() ? $this->userArray($i->getAuthor()) : null);

                            if ('video' == $type) {
                                if (in_array('watchlisted', $extrafields)) $data['watchlisted'] = $this->get('video.playlist')->isInPlaylist($i, $user);
                                if (in_array('duration', $extrafields)) $data['duration'] = $i->getDuration();
                                $data['category_id'] = $i->getVideocategory()->getId();
                                $data['provider'] =  $i->getProvider();
                            } else {
                                 if (in_array('album', $extrafields)) $data['album'] = array('id'=>$i->getAlbum()->getId(),
                                    'title'=>$i->getTitle()->getId(), 'photoCount'=>$i->getAlbum()->getPhotoCount());
                            }
                        }
                    }

                    if ($this->_evaluateConditions($type, array('user', 'team', 'idol'))) {
                        if ('user' == $type) $data['username'] = $this->userArray($i);
                        if ('user' == $type || 'idol' == $type) {
                            $data['firstname'] = $i->getFirstname();
                            $data['lastname'] = $i->getLastname();
                        }

                        if (in_array('photoCount', $extrafields)) $data['photoCount'] = $i->getPhotoCount();
                        if (in_array('videoCount', $extrafields)) $data['videoCount'] = $i->getVideoCount();
                        if (in_array('fanCount', $extrafields)) $data['fanCount'] = $i->getFanCount();
                        if (in_array('splash', $extrafields)) $data['splash'] = $this->imageValues($i->getSplash());
                    }

                    $return[$type][] = $data;
                }
                $count = count($return[$type]);
                if ($count > $highestcount) $highestcount = $count;
            }

            $pagination['count'] = $highestcount;

            return $this->result($return, $pagination);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }


    private function _evaluateConditions($what, array $values)
    {
        $final=false;
        for ($j=0; (!$final && $j < count($values)); $j++ ) {
            if ($what == $values[$j]) $final = true;
        }
        return $final;
    }
}
