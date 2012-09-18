<?php

namespace Dodici\Fansworld\WebBundle\Controller;

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
 */
class ApiController extends SiteController
{
    const TIMESTAMP_MARGIN = 120;
    
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
     * test method
     * @Route("/test", name="api_test")
     */
    public function testAction()
    {
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
     * Returns Apikey entity corresponding to $key
     * @param string $key
     */
    private function getApiKeyByKey($key)
    {
        $apikey = $this->getRepository('Apikey')->findOneBy(array('apikey' => $key));
        return $apikey;
    }
}
