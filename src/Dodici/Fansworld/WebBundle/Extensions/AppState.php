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

}