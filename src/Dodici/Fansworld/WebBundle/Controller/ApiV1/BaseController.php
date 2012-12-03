<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

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
        $imageurl = null;
        if ($user->getImage()) {
            $imageurl = $this->get('appmedia')->getImageUrl($user->getImage());
        }
        
        $idolcount = $this->getRepository('Idolship')->countBy(array('author' => $user->getId()));
        $teamcount = $this->getRepository('Teamship')->countBy(array('author' => $user->getId()));
        
        return array(
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'image' => $imageurl,
            'idolcount' => $idolcount,
            'teamcount' => $teamcount
        );
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
            $code = $e->getStatusCode();
        } else {
            $code = 400;
        }
        
        $return = array(
            'code' => $code,
            'message' => $e->getMessage()
        );
        
        return $this->jsonResponse($return, $code);
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
        
        if ($realtoken != $token) throw new HttpException(401, 'Invalid user token');
        
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
            $metadata['pagination']['count'] = count($array);
        }
        
        return $this->jsonResponse(array(
            'result' => $array,
            'metadata' => $metadata
        ));
    }
}
