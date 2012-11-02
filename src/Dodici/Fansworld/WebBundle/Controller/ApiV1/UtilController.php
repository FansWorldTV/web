<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

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

/**
 * API controller - Util
 * V1
 * @Route("/api_v1")
 */
class UtilController extends BaseController
{
	/**
     * Get server timestamp
     * @Route("/sync", name="api_v1_sync")
     */
    public function syncAction()
    {
        $now = new \DateTime();
        return $this->jsonResponse($now->format('U'));
    }
    
	/**
     * [signed] test method (user token)
     * @Route("/test/token", name="api_v1_test_token")
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
     * @Route("/test/signature", name="api_v1_test_signature")
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
}
