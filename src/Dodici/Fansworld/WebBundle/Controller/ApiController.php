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
     * [signed] Login
     * 
     * @Route("/login", name="api_login")
     * @Method({"POST"})
     *
     * Get params:
     * - username/email: string
     * - password: string, plain text
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
                    
                    $imageurl = null;
                    if ($user->getImage()) {
                        $imageurl = $this->get('appmedia')->getImageUrl($user->getImage());
                    }
                    
                    $return = array(
                        'token' => $token,
                        'user' => array(
                            'id' => $user->getId(),
                            'username' => $user->getUsername(),
                            'email' => $user->getEmail(),
                            'firstname' => $user->getFirstname(),
                            'lastname' => $user->getLastname(),
                            'image' => $imageurl
                        )
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
