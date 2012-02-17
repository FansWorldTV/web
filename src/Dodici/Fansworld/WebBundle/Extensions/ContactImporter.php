<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;
use Artseld\OpeninviterBundle\ArtseldOpeninviter\ArtseldOpeninviter;

class ContactImporter
{
	protected $request;
	protected $container;
	protected $inviter;

    function __construct(Container $container)
    {
        $this->request = Request::createFromGlobals();
        $this->container = $container;
        $this->inviter = new ArtseldOpeninviter( $this->container );
    }

    /**
     * Get contacts from OpenInviter
     * @param string $username
     * @param string $password
     * @param string $provider
     */
    public function import($username, $password, $provider)
    {
    	$this->inviter->getPlugins();
    	$this->inviter->startPlugin($provider);
    	$this->inviter->login($username, $password);
    	return $this->inviter->getMyContacts(); 
    	exit;
    }    
}