<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class AppState
{

    protected $session;
    protected $request;
    protected $em;

    function __construct(Session $session, EntityManager $em)
    {
        $this->session = $session;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
    }

    public function getMobile()
    {
        return (strpos($this->request->getHost(), 'm.') === 0);
    }

    public function getCulture($locale)
    {
    	switch ($locale) {
    		case 'es':
    			return 'es_LA'; break;
    		case 'en':
    		default:
    			return 'en_US'; break;
    	}
    }
}