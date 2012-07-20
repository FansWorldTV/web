<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Preferences
{
	protected $request;
	protected $security_context;
	protected $em;
    protected $user;
    protected $session;
    protected $appstate;
    
    const PREFIX = 'fw.preferences.';

    function __construct(SecurityContext $security_context, EntityManager $em, $session, $appstate)
    {
        $this->request = Request::createFromGlobals();
        $this->security_context = $security_context;
        $this->em = $em;
        $this->user = null;
        $this->session = $session;
        $this->appstate = $appstate;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Set a preference key with a value
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        if ($this->user) {
            $prefs = $this->user->getPreferences();
            $prefs[$key] = $value;
            $this->user->setPreferences($prefs);
            
            $this->em->persist($this->user);
            $this->em->flush();
        }
        $this->session->set(self::PREFIX.$key, $value);
        
        return true;
    }    
    
    /**
     * get a preference value
     * @param string $key
     */
    public function get($key)
    {
        $value = $this->session->get(self::PREFIX.$key);
        if (!$value && $this->user) {
            $prefs = $this->user->getPreferences();
            if (isset($prefs[$key])) {
                $value = $prefs[$key];
                $this->populate();
            }
        }
        return $value;
    }
    
    /**
     * populate session from user
     */
    public function populate()
    {
        if ($this->user) {
            $prefs = $this->user->getPreferences();
            foreach ($prefs as $key => $value) {
                $this->session->set(self::PREFIX.$key, $value);
            }
        }
    }
}