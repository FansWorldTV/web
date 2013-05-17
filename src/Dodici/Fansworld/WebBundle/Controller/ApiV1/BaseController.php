<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Share;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dodici\Fansworld\WebBundle\Controller\SiteController;

/**
 * API V1 base controller
 * REST, json
 *
 * How to construct a signature hash:
 *
 * Each unique API consumer (Apikey) has a key and a secret, both unique to them
 * /api/sync provides current server timestamp. TIMESTAMP_MARGIN is the late limit of the GET provided ts
 *
 * Concatenate: 'api_key=' + <key> + '&api_timestamp=' + <timestamp> + <secret>
 * sha1 the result, this is the <signature string>
 * To sign a request, add the GET params:
 * api_key = <key>
 * api_timestamp = <timestamp>
 * api_signature = <signature string>
 */
class BaseController extends SiteController
{
    const TIMESTAMP_MARGIN = 120;
    const TOKEN_SECRET = 'gafd7u8adf9';
    const LIMIT_DEFAULT = 10;
    const DEFAULT_IMAGE_FORMAT = 'small';
    const DEFAULT_SPLASH_FORMAT = 'medium';

    /**
     * Does this request have a valid signature behind it?
     */
    protected function hasValidSignature()
    {
        $request = $this->getRequest();
        $key = $request->get('api_key');
        $timestamp = $request->get('api_timestamp');
        $signature = $request->get('api_signature');

        return $this->validateSignature($key, $timestamp, $signature);
    }

    /**
     * Validate a signature
     * @param string $key
     * @param int $timestamp
     * @param string $signature
     */
    protected function validateSignature($key, $timestamp, $signature)
    {
		if (!$timestamp) throw new HttpException(400, 'Requires timestamp');
        if (!is_numeric($timestamp)) throw new HttpException(400, 'Invalid timestamp');
        $apikey = $this->getApiKeyByKey($key);
		$now = new \DateTime();
		$currentts = (int)$now->format('U');
		$tsdiff = abs($timestamp - $currentts);
		if ($tsdiff > self::TIMESTAMP_MARGIN) throw new HttpException(400, 'Timestamp is too old');

		if (!$apikey) throw new HttpException(400, 'Invalid api key');
		if (!$signature) throw new HttpException(400, 'Requires signature');

		$sig = $this->createSignature($key, $timestamp, $apikey->getSecret());

		return ($sig == $signature);
    }

    /**
     * Create a signature from parameters
     * @param string $key
     * @param int $timestamp
     * @param string $secret
     */
    protected function createSignature($key, $timestamp, $secret)
    {
        $str = 'api_key='.$key.'&api_timestamp='.$timestamp.$secret;
        return sha1($str);
    }

    /**
     * Get the current apikey in use
     *
     * @return Apikey
     */
    protected function getApiKey()
    {
        $request = $this->getRequest();
        $key = $request->get('api_key');
        return $this->getApiKeyByKey($key);
    }

    /**
     * Returns Apikey entity corresponding to $key
     * @param string $key
     */
    protected function getApiKeyByKey($key)
    {
        $apikey = $this->getRepository('Apikey')->findOneBy(array('apikey' => $key));
        return $apikey;
    }

    /**
     * Generate a semi-permanent hash token for a user under an api
     * @param User $user
     */
    protected function generateUserApiToken(User $user, Apikey $apikey)
    {
        return sha1(
            $user->getId().'|'.
            $user->getEmail().'|'.
            $user->getUsername().'|'.
            $apikey->getApikey().'|'.
            $user->getPassword().'|'.
            self::TOKEN_SECRET
        );
    }

    protected function authUser($username, $password)
    {
        $usermanager = $this->get('app_user.user_manager');
        $user = $usermanager->findUserByUsernameOrEmail($username);
        if ($user) {
            $encoder_service = $this->get('security.encoder_factory');
            $encoder = $encoder_service->getEncoder($user);
            $encoded_pass = $encoder->encodePassword($password, $user->getSalt());

            if ($user->getPassword() == $encoded_pass) {
                return $user;
            } else {
                // Bad password
                throw new HttpException(401, 'Invalid password');
            }
        } else {
            // Bad username/mail
            throw new HttpException(401, 'Invalid username or email');
        }
    }

    protected function authFacebook($fbid, $accesstoken)
    {
        if (!$fbid || !$accesstoken) throw new HttpException(400, 'Requires facebook_id and access_token');
        if (!is_numeric($fbid)) throw new HttpException(400, 'Invalid facebook_id');

        $facebook = $this->get('fos_facebook.api');
        $facebook->setAccessToken($accesstoken);
        $data = $facebook->api('/me');

        if (!$data || !(isset($data['id']) && ($data['id'] == $fbid)))
            throw new HttpException(401, 'Invalid facebook_id or access_token');

        return $data;
    }

    protected function userArray(User $user)
    {
        $idolcount = $this->getRepository('Idolship')->countBy(array('author' => $user->getId()));
        $teamcount = $this->getRepository('Teamship')->countBy(array('author' => $user->getId()));
        $followcount = $this->getRepository('Friendship')->countBy(array('author' => $user->getId(), 'active' => true));

        return array(
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'image' => $this->imageValues($user->getImage()),
            'splash' => $this->imageValues($user->getSplash(), $this->getImageFormat('splash')),
            'fanCount' => $user->getFanCount(),
            'videoCount' => $user->getVideoCount(),
            'idolFollowCount' => $idolcount,
            'teamFollowCount' => $teamcount,
            'fanFollowCount' => $followcount
        );
    }

    protected function jsonComment(Comment $comment, $event=false, $user=null)
    {
        $appstate = $this->get('appstate');

        $author = $comment->getAuthor() ? $this->userArray($comment->getAuthor()) : null;

        $type = $comment->getTypeName();

        $tag = $this->getTagItem($comment);

        $commentArray = array(
            'id' => $comment->getId(),
            'canDelete' => $appstate->canDelete($comment),
            'type' => $type,
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('U'),
            'commentCount' => $comment->getCommentCount(),
            'author' => $author,
            'share' => $tag
        );

        if ($user) $commentArray['liked'] = ($this->get('liker')->isLiking($comment, $user) ? true : false);

        if ($event) {
            $eventship = $this->getRepository('Eventship')->findOneBy(array('author' => $comment->getAuthor()->getId(), 'event' => $event->getId()));
            $commentArray['following_team'] = $comment->getTeam()->getId();
            $commentArray['following_type'] = $eventship->getType();
        }

        return $commentArray;
    }

    protected function getTagItem(Comment $comment)
    {
        $share = $comment->getShare();
        if (!$share) return null;
        $validTypes = Share::getTypes();

        $tag = array();

        foreach ($validTypes as $type) {
            $getType = 'get' . ucfirst($type);
            if (!is_null($share->$getType())) {
                $tag_item = $share->$getType();
                $tag['type'] = $type;
                $tag['id'] = $tag_item->getId();
                $tag['title'] = $tag_item->__toString();
                $tag['likecount'] = method_exists($tag_item, 'getLikeCount') ? $tag_item->getLikeCount() : null;
                $tag['image'] = null;
                if (method_exists($tag_item, 'getImage')) {
                    $image = $tag_item->getImage();
                    if ($image) {
                        $tag['image'] = $this->imageValues($image);
                    }
                }

                break;
            }
        }


        return $tag;
    }

    protected function addIdolsTeams(User $user)
    {
        $request = $this->getRequest();
        $fanmaker = $this->get('fanmaker');

        $idols = $request->get('idol');
        $teams = $request->get('team');

        if ($idols && is_array($idols)) {
            foreach ($idols as $i) {
                $idol = $this->getRepository('Idol')->find($i);
                $fanmaker->addFan($idol, $user);
            }
        }

        if ($teams && is_array($teams)) {
            foreach ($teams as $t) {
                $team = $this->getRepository('Team')->find($t);
                $fanmaker->addFan($team, $user);
            }
        }
    }

    protected function plainException(\Exception $e)
    {
        if ($e instanceof HttpException) {
            $strcode = $e->getStatusCode();
            if (strpos($strcode, '-') !== false) {
                $codes = explode('-', $strcode);
                $appcode = $codes[0];
                $httpcode = $codes[1];
            } else {
                $httpcode = $strcode;
                $appcode = $strcode;
            }
        } else {
            $httpcode = 400;
            $appcode = 400;
        }

        $return = array(
            'code' => $appcode,
            'message' => $e->getMessage()
        );

        return $this->jsonResponse($return, $httpcode);
    }

    protected function pagination($allowedsorts = array(), $defaultsort = null, $defaultorder = null, $allowedorders = array('ASC', 'DESC'))
    {
        $request = $this->getRequest();
        $limit = $request->get('limit', self::LIMIT_DEFAULT);
        $offset = $request->get('offset');
        $page = $request->get('page');
        $sort = $request->get('sort', $defaultsort);
        $sortorder = $request->get('sort_order', $defaultorder ?: 'DESC');
        $sortorder = ($sortorder ? strtoupper($sortorder) : null);

        if ($offset && $page) throw new HttpException(400, 'Cannot specify both offset and page at the same time');
        if ($limit && !is_numeric($limit)) throw new HttpException(400, 'Invalid limit');
        if ($offset && !is_numeric($offset)) throw new HttpException(400, 'Invalid offset');

        if ($sort) {
            if (!in_array($sort, $allowedsorts)) throw new HttpException(400, 'Invalid sort');
            if (!in_array($sortorder, $allowedorders)) throw new HttpException(400, 'Invalid sort_order');
        }

        if ($page) $offset = $page * $limit;

        if (!$page) $page = floor($offset / $limit);

        $return = array(
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page
        );

        if ($sort) {
            $return['sort'] = $sort;
            $return['sort_order'] = $sortorder;
        }

        return $return;
    }

    protected function checkUserToken($userid, $token)
    {
        $user = $this->getRepository('User')->findOneBy(array('id' => $userid, 'enabled' => true));
        if (!$user) throw new HttpException(404, 'User not found');
        if (!$token) throw new HttpException(400, 'Requires user_token');
        if (!$this->hasValidSignature()) throw new HttpException(401, 'Invalid signature');

        $apikey = $this->getApiKey();
        $realtoken = $this->generateUserApiToken($user, $apikey);

        if ($realtoken != $token) throw new HttpException('601-401', 'Invalid user token');

        if ($user->isExpired()) throw new HttpException('602-401', 'User account has expired');

        return $user;
    }

    protected function getExtraFields($allowedfields)
    {
        $request = $this->getRequest();
        $extrafieldsstr = $request->get('extra_fields');
        $extrafields = array();
        if ($extrafieldsstr) {
            $exp = explode(',', $extrafieldsstr);
            foreach ($exp as $x) {
                if ($x && in_array($x, $allowedfields)) {
                    if (in_array($x, $extrafields)) throw new HttpException(400, 'Duplicate extra field: "'.$x.'"');
                    $extrafields[] = $x;
                } else {
                    throw new HttpException(400, 'Invalid extra field: "'.$x.'"');
                }
            }
        }

        return $extrafields;
    }

    protected function result($array, $pagination = null)
    {
        $metadata = array();
        $request = $this->getRequest();
        $metadata['uri'] = $request->getUri();
        $metadata['method'] = $request->getMethod();
        $getparams = $request->query->all();
        $postparams = $request->request->all();
        $metadata['parameters'] = array('query' => $getparams, 'request' => $postparams);

        if ($pagination) {
            $metadata['pagination'] = $pagination;
            if (!isset($pagination['count'])) $metadata['pagination']['count'] = count($array);
        }

        return $this->jsonResponse(array(
            'result' => $array,
            'metadata' => $metadata
        ));
    }

    protected function videoValues(Video $video, $extrafields = array(), $user = null)
    {
        $rv = array(
            'id' => $video->getId(),
            'title' => (string)$video,
            'image' => $this->imageValues($video->getImage()),
            'highlight' => $video->getHighlight(),
            'category_id' => $video->getVideocategory()->getId(),
            'genre_id' => $video->getGenre() ? (int)$video->getGenre()->getId() : null,
            'genreparent_id' => $video->getGenre()->getParent() ? (int)$video->getGenre()->getParent()->getId() : null,
            'provider' => ($video->getYoutube() ? 'youtube' : 'kaltura')
        );

        foreach ($extrafields as $x) {
            switch ($x) {
                case 'author':
                    $rv['author'] = $video->getAuthor() ? $this->userArray($video->getAuthor()) : null;
                    break;
                case 'createdAt':
                    $rv['createdAt'] = (int)$video->getCreatedAt()->format('U');
                    break;
                case 'watchlisted':
                    if ($user) $rv[$x] = $this->get('video.playlist')->isInPlaylist($video, $user);
                    break;
                case 'liked':
                    if ($user) $rv[$x] = $this->get('liker')->isLiking($video, $user) ? true : false;
                    break;
                case 'url':
                    $rv[$x] = $this->get('router')->generate('video_show', array('id' => $video->getId(), 'slug' => $video->getSlug()), true);
                    break;
                default:
                    $methodname = 'get'.ucfirst($x);
                    $rv[$x] = $video->$methodname();
                    break;
            }
        }

        return $rv;
    }

    protected function getImageFormat($suffix = null)
    {
        $request = $this->getRequest();
        $default = self::DEFAULT_IMAGE_FORMAT;
        if ($suffix == 'splash') $default = self::DEFAULT_SPLASH_FORMAT;
        $imageformat = $request->get('imageformat'.($suffix ? ('_'.$suffix) : ''), $default);
        return $imageformat;
    }

    protected function imageValues($image, $imageformat=null)
    {
        if (!$imageformat) $imageformat = $this->getImageFormat();

        if (!$image) return null;
        return array(
            'id' => $image->getId(),
            'url' => $this->getImageUrl($image, $imageformat)
        );
    }
}
