<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Preference controller.
 * @Route("/preferences")
 */
class PreferenceController extends SiteController
{
    /**
     * Set key with value
     * @Route("/set", name="preference_set")
     */
    public function setAction()
    {
        $request = $this->getRequest();
        $key = $request->get('key');
        $value = $request->get('value');
        
        if (!$key) throw new HttpException(400, 'No key provided');
        if (!$value) throw new HttpException(400, 'No value provided');
        
        return $this->jsonResponse($this->get('preferences')->set($key, $value));
    }

    /**
     * Get value of key
     * @Route("/get", name="preference_get")
     */
    public function getAction()
    {
        $request = $this->getRequest();
        $key = $request->get('key');
        
        if (!$key) throw new HttpException(400, 'No key provided');
        
        return $this->jsonResponse($this->get('preferences')->get($key));
    }

}
