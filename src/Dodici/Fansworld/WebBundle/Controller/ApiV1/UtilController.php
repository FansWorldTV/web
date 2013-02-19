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
        return $this->jsonResponse((int)$now->format('U'));
    }
    
    /**
     * Get available locales
     * @Route("/locales", name="api_v1_locales")
     * 
     * @return
     * array (
     * 		default: string,
     * 		current: string,
     * 		locales: array(
     * 			array(
     * 				<code>: <localized name>
     * 			),
     * 			...
     * 		)
     * )
     */
    public function localesAction()
    {
        try {
            $locales = $this->container->getParameter('jms_i18n_routing.locales');
            $default = $this->container->getParameter('jms_i18n_routing.default_locale');
            $current = $this->get('session')->getLocale();
            
            $return = array();
            $return['default'] = $default;
            $return['current'] = $current;
            $trans = $this->get('translator');
            foreach ($locales as $l) {
                $return['locales'][$l] = $trans->trans('locale_'.$l);
            }
            
            return $this->result($return);
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
    
    /**
     * test method (kaltura)
     * @Route("/test/kaltura", name="api_v1_test_kaltura")
     * 
     * ???
     */
    public function testKalturaAction()
    {
        $kf = $this->get('fansworld.kaltura.notification');
        $kf->process($this->getRequest());
        
        return new Response('OK');
    }
    
	/**
     * test method (user)
     * @Route("/test/user", name="api_v1_test_user")
     * 
     * ???
     */
    public function testUserAction()
    {
        try {
            $request = $this->getRequest();
            $userid = $request->get('user_id');
            
            $user = $this->getRepository('User')->find($userid);
            if (!$user) throw new HttpException(400, 'Invalid user id');
            
            return $this->jsonResponse($this->userArray($user));
            
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
