<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;

/**
 * API controller
 * REST, json
 * 
 * @Route("/api")
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
class ApiController extends SiteController
{
    const TIMESTAMP_MARGIN = 120;
    const TOKEN_SECRET = 'gafd7u8adf9';
    
    /**
     * Get server timestamp
     * @Route("/sync", name="api_sync")
     */
    public function syncAction()
    {
        $now = new \DateTime();
        return $this->jsonResponse($now->format('U'));
    }
    
    /**
     * [signed] Register
     * 
     * @Route("/register", name="api_register")
     * @Method({"POST"})
     * 
     * Post params:
     * - email: string
     * - password: string
     * - firstname: string
     * - lastname: string
     * - <optional> idol[] : array (int, ...)
     * - <optional> team[] : array (int, ...)
     * - <optional> device_id: string (iphone device id for APNS)
     * - [signature params]
     * 
     * @return
     *
     * on success:
     * @see ApiController::loginAction()
     */
    public function registerAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                
                $username = null; $email = null; $password = null; $firstname = null; $lastname = null;
                $rfields = array('username', 'email', 'password', 'firstname', 'lastname');
                foreach ($rfields as $rf) {
                    ${$rf} = $request->get($rf);
                    if (!${$rf}) throw new HttpException(400, 'Required parameters: ' . join(', ', $rfields));
                }
                
                $usermanager = $this->get('app_user.user_manager');
                $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
                
                $user = $usermanager->createUser();
                $user->setFirstname($firstname);
                $user->setLastname($lastname);
                $user->setEmail($email);
                $user->setPlainPassword($password);
                
                $existsmail = $usermanager->findUserByEmail($user->getEmail());
                if ($existsmail) throw new HttpException(400, 'User with email '.$user->getEmail().' already exists');
                
                $username = $user->getFirstname().'.'.$user->getLastname();
                $username = str_replace(' ', '.', $username);
                $exists = $usermanager->findUserByUsername($username);
                if ($exists) $username .= '.' . uniqid();
                $user->setUsername($username);
                
                if ($confirmationEnabled) {
                    $user->setEnabled(false);
                } else {
                    $user->setConfirmationToken(null);
                    $user->setEnabled(true);
                }
                
                $validator = $this->get('validator');
                $errors = $validator->validate($user, array('Registration'));
            
                if (count($errors) > 0) {
                    $errorstr = (string)$errors;
                    $errorstr = str_replace('Application\Sonata\UserBundle\Entity\User.', '', $errorstr);
                    throw new HttpException(400, $user->getUsername() . ' ' .$errorstr);
                }
        
                $usermanager->updateUser($user);
                
                if ($confirmationEnabled) {
                    $this->get('fos_user.mailer')->sendConfirmationEmailMessage($user);
                }
                
                if ($user->getId()) {
                    $token = $this->generateUserApiToken($user, $this->getApiKey());
                    
                    $return = array(
                        'token' => $token,
                        'user' => $this->userArray($user)
                    );
                    
                    $this->addIdolsTeams($user);
                    
                    return $this->jsonResponse($return);
                    
                } else {
                    throw new HttpException(500, 'Something went wrong');
                }
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
        
    /**
     * [signed] Login
     * 
     * @Route("/login", name="api_login")
     * @Method({"POST"})
     *
     * Post params:
     * - username/email: string
     * - password: string, plain text
     * - <optional> device_id: string (iphone device id for APNS)
     * - [signature params]
     * 
     * @return 
     * array (
     * 		token: hash used in other requests - string,
     * 		user: array (
     * 			id: int,
     * 			username: string,
     * 			email: string,
     * 			firstname: string,
     * 			lastname: string,
     * 			image: string (avatar url) | null
     * 		)
     * )
     */
    public function loginAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $username = $request->get('username');
                $email = $request->get('email');
                $password = $request->get('password');
                
                if (!$username && !$email) throw new HttpException(400, 'Requires username or email');
                if (!$password) throw new HttpException(400, 'Requires password');
                
                $user = $this->authUser($username ?: $email, $password);
                
                if ($user) {
                    $token = $this->generateUserApiToken($user, $this->getApiKey());
                    
                    $return = array(
                        'token' => $token,
                        'user' => $this->userArray($user)
                    );
                    
                    return $this->jsonResponse($return);
                    
                } else {
                    // Bad username/mail
                    throw new HttpException(401, 'Invalid username or password');
                }
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] Facebook Connect
     * 
     * @Route("/connect/facebook", name="api_connect_facebook")
     * 
     * - If user already exists and is linked to FB: regular login
     * - If user with same email already exists, but is not linked to FB, links account to FB
     * - If user does not exist, registers the account
     * 
     * Get params:
     * - facebook_id: int
     * - access_token: string (facebook's access token for verification purposes)
     * - <optional> idol[] : array (int, ...)
     * - <optional> team[] : array (int, ...)
     * - <optional> device_id: string (iphone device id for APNS)
     * - [signature params]
     * 
     * @return
     * @see ApiController::loginAction()
     */
    public function facebookLoginAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $fbid = $request->get('facebook_id');
                $accesstoken = $request->get('access_token');
                
                $fbdata = $this->authFacebook($fbid, $accesstoken);
                
                $fbmanager = $this->get('my.facebook.user');
                $user = $fbmanager->loadUserByUsername($fbid, $accesstoken);
                
                if (!$user) throw new HttpException(401, 'Could not authenticate');
                
                $token = $this->generateUserApiToken($user, $this->getApiKey());
                
                $this->addIdolsTeams($user);
                
                $return = array(
                    'token' => $token,
                    'user' => $this->userArray($user)
                );
                
                return $this->jsonResponse($return);
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch(\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * [signed] test method (user token)
     * @Route("/test/token", name="api_test_token")
     * 
     * Get params:
     * - user_id: int
     * - user_token: string
     * - [signature params]
     */
    public function testTokenAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $userid = $request->get('user_id');
                $usertoken = $request->get('user_token');
                
                if (!$userid || !$usertoken) throw new HttpException(400, 'User id and token required');
                
                $user = $this->getRepository('User')->find($userid);
                if (!$user) throw new HttpException(400, 'Invalid user id');
                
                $realtoken = $this->generateUserApiToken($user, $this->getApiKey());
                
                if ($usertoken === $realtoken) {
                    return new Response('valid user token');
                } else {
                    $txt = 'invalid user token<br>';
                    $txt .= 'user id: ' . $userid . '<br>';
                    $txt .= 'user token: ' . $usertoken . '<br><br>';
                    $txt .= 'expected token: ' . $realtoken;
                    
                    return new Response($txt);
                }
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
    /**
     * test method (signature)
     * @Route("/test/signature", name="api_test_signature")
     * 
     * Get params:
     * - api_key: string (your unique api consumer identifier)
     * - api_timestamp: int (must be within sync method compliance)
     * - api_signature: string (signature hash)
     */
    public function testSignatureAction()
    {
        try {
            if ($this->hasValidSignature()) {
                return new Response('valid signed request');
            } else {
                $request = $this->getRequest();
                $key = $request->get('api_key');
                $timestamp = $request->get('api_timestamp');
                $signature = $request->get('api_signature');
                $apikey = $this->getApiKeyByKey($key);
                
                $txt = 'invalid signed request<br>';
                $txt .= 'key: ' . $key . '<br>';
                $txt .= 'timestamp: ' . $timestamp . '<br>';
                $txt .= 'signature: ' . $signature . '<br>';
                $txt .= '<br>';
                
                if ($apikey) {
                    $txt .= 'expected sig: ' . $this->createSignature($key, $timestamp, $apikey->getSecret());
                } else {
                    $txt .= 'no apikey found for key';
                }
                
                return new Response($txt);
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
    /**
     * Does this request have a valid signature behind it?
     */
    private function hasValidSignature()
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
    private function validateSignature($key, $timestamp, $signature)
    {
		if (!$timestamp || !is_numeric($timestamp)) throw new HttpException(400, 'Bad timestamp');
        $apikey = $this->getApiKeyByKey($key);
		$now = new \DateTime();
		$currentts = $now->format('U');
		$tsdiff = abs($timestamp - $currentts);
		if ($tsdiff > self::TIMESTAMP_MARGIN) throw new HttpException(400, 'Timestamp is too old');
		
		if (!$apikey) throw new HttpException(400, 'Bad api key');
		if (!$signature) throw new HttpException(400, 'Bad signature');
		
		$sig = $this->createSignature($key, $timestamp, $apikey->getSecret());
		
		return ($sig == $signature);
    }
    
    /**
     * Create a signature from parameters
     * @param string $key
     * @param int $timestamp
     * @param string $secret
     */
    private function createSignature($key, $timestamp, $secret)
    {
        $str = 'api_key='.$key.'&api_timestamp='.$timestamp.$secret;
        return sha1($str);
    }
    
    /**
     * Get the current apikey in use
     * 
     * @return Apikey
     */
    private function getApiKey()
    {
        $request = $this->getRequest();
        $key = $request->get('api_key');
        return $this->getApiKeyByKey($key);
    }
        
    /**
     * Returns Apikey entity corresponding to $key
     * @param string $key
     */
    private function getApiKeyByKey($key)
    {
        $apikey = $this->getRepository('Apikey')->findOneBy(array('apikey' => $key));
        return $apikey;
    }
    
    /**
     * Generate a semi-permanent hash token for a user under an api
     * @param User $user
     */
    private function generateUserApiToken(User $user, Apikey $apikey)
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
    
    private function authUser($username, $password)
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
                throw new HttpException(401, 'Invalid username or password');
            }
        } else {
            // Bad username/mail
            throw new HttpException(401, 'Invalid username or password');
        }
    }
    
    private function authFacebook($fbid, $accesstoken)
    {
        if (!$fbid || !$accesstoken) throw new HttpException(400, 'Requires facebook_id and access_token');
        if (!is_numeric($fbid)) throw new HttpException(400, 'Invalid facebook_id');
        
        $facebook = $this->get('fos_facebook.api');
        $facebook->setAccessToken($accesstoken);
        $data = $facebook->api('/me');
        
        if (!$data || !(isset($data['verified']) && $data['verified']))
            throw new HttpException(401, 'Invalid facebook_id or access_token');
        
        return $data;
    }
    
    private function userArray(User $user)
    {
        $imageurl = null;
        if ($user->getImage()) {
            $imageurl = $this->get('appmedia')->getImageUrl($user->getImage());
        }
        
        return array(
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'image' => $imageurl
        );
    }
    
    private function addIdolsTeams(User $user)
    {
        $request = $this->getRequest();
        $fanmaker = $this->get('fanmaker');
        
        $idols = $request->get('idol');
        $teams = $request->get('team');
        
        foreach ($idols as $i) {
            $idol = $this->getRepository('Idol')->find($i);
            $fanmaker->addFan($idol, $user);
        }
        
        foreach ($teams as $t) {
            $team = $this->getRepository('Team')->find($t);
            $fanmaker->addFan($team, $user);
        }
    }
    
    private function plainException(\Exception $e)
    {
        if ($e instanceof HttpException) {
            $code = $e->getStatusCode();
        } else {
            $code = 400;
        }
        
        return new Response($e->getMessage(), $code);
    }
}
