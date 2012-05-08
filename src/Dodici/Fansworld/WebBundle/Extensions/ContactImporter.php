<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Application\Sonata\UserBundle\Entity\User;

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
    
    /**
     * Generate a token for the invite users url
     * @param User $user
     */
    public function inviteToken(User $user)
    {
    	return sha1($user->getId().'-ashurbanipal-'.$user->getUsername());
    }
    
    public function inviteUrl(User $user)
    {
    	$router = $this->container->get('router');
    	return $router->generate(
    		'fos_user_registration_register', 
    		array(
    			'inviter' => $user->getUsername(), 
    			'token' => $this->inviteToken($user)
    		), true);
    }
}