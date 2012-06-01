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
    protected $appstate;
    protected $router;

    function __construct(SecurityContext $security_context, EntityManager $em, $facebook, $appstate, $router)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        $this->facebook = $facebook;
        $this->appstate = $appstate;
        $this->router = $router;
    }

    /**
     * Get friends from facebook
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookFriends($user=null)
    {
    	$friends = $this->api('/{uid}/friends', $user);
    	if (isset($friends['data'])) $friends = $friends['data'];
    	return $friends;
    }
    
    /**
     * Get users that are facebook friends
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookFansworld($user=null, $limit=null, $offset=null)
    {
    	$friends = $this->facebookFriends($user);
    	if (!$friends) throw new \Exception('Sin amigos');
    	$ids = array();
    	foreach ($friends as $friend) {
    		$ids[] = $friend['id'];
    	}
    	$friendrepo = $this->em->getRepository('Application\\Sonata\\UserBundle\\Entity\\User');
    	$fwfriends = $friendrepo->findBy(
    		array('enabled' => true, 'linkfacebook' => true, 'facebookId' => $ids),
    		array('lastname' => 'ASC','firstname' => 'ASC'),
    		$limit,
    		$offset
    	);
    	return $fwfriends;
    }
    
    public function upload($entity)
    {
    	if (!property_exists($entity, 'author')) throw new \Exception('La entidad no es compatible');
    	$user = $entity->getAuthor();
    	if (!($user instanceof User)) throw new \Exception('La entidad no tiene autor');
    	
    	$type = $this->appstate->getType($entity);
    	$url = $this->router->generate($type.'_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), true);
    	
    	return $this->verb('upload', array('other' => $url), $user);
    }
    
    public function verb($verb, $params, $user)
    {
    	return $this->api('/{uid}/fansworld:'.$verb, $user, 'POST', $params);
    }
    
    private function api($url, $user=null, $method='GET', $params=array())
    {
    	if (!$user) {
    		$user = $this->user;
    	}
    	if (!($user instanceof User)) throw new \Exception('Falta usuario');
    	if (!$user->getFacebookId()) throw new \Exception('Usuario sin ID Facebook');
    	
    	$url = str_replace('{uid}', $user->getFacebookId(), $url);
    	
    	return $this->facebook->api($url, $method, $params);
    }
}