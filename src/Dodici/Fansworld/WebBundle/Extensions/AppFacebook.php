<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class AppFacebook
{
	protected $security_context;
    protected $request;
    protected $em;
    protected $user;
    protected $facebook;

    function __construct(SecurityContext $security_context, EntityManager $em, $facebook)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        $this->facebook = $facebook;
    }

    /**
     * Get friends from facebook
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookFriends($user=null)
    {
    	if (!$user) {
    		$user = $this->user;
    	}
    	if (!($user instanceof User)) throw new \Exception('Falta usuario');
    	if (!$user->getFacebookId()) throw new \Exception('Usuario sin ID Facebook');
    	
    	$friends = $this->facebook->api('/me/friends');
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
    	$friendrepo = $this->em->getRepository('Application\\Sonata\\UserBundle\\Entity\\User');
    	$fwfriends = $friendrepo->findBy(array('enabled' => true, 'linkfacebook' => true, 'facebookId' => $ids));
    	return $fwfriends;
    }
}