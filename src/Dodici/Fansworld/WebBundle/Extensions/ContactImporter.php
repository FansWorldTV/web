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
     * Get friends from facebook
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookFriends($user=null)
    {
    	if (!$user) {
    		$user = $this->container->get('security.context')->getToken()->getUser();
    	}
    	if (!($user instanceof User)) throw new \Exception('Falta usuario');
    	if (!$user->getFacebookId()) throw new \Exception('Usuario sin ID Facebook');
    	
    	$facebook = $this->container->get('fos_facebook.api');
    	$friends = $facebook->api('/me/friends');
    	if (isset($friends['data'])) $friends = $friends['data'];
    	return $friends;
    }
    
    /**
     * Get users that are facebook friends
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookFansworld($user=null)
    {
    	$friends = $this->facebookFriends($user);
    	if (!$friends) throw new \Exception('Sin amigos');
    	$ids = array();
    	foreach ($friends as $friend) {
    		$ids[] = $friend['id'];
    	}
    	$friendrepo = $this->container->get('doctrine.orm.entity_manager')->getRepository('Application\\Sonata\\UserBundle\\Entity\\User');
    	$fwfriends = $friendrepo->findBy(array('enabled' => true, 'facebookId' => $ids));
    	return $fwfriends;
    }
}