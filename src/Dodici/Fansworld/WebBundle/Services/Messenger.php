<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Share;
use Dodici\Fansworld\WebBundle\Entity\MessageTarget;
use Dodici\Fansworld\WebBundle\Entity\Message;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Messenger
{
    protected $security_context;
    protected $em;
	protected $appstate;
    protected $user;

    function __construct(EntityManager $em, SecurityContext $security_context, $appstate)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->appstate = $appstate;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Send a message to the users, with shared content optionally
     * @param array(User) $targets
	 * @param string|null $content - only nullable when sharing
	 * @param mixed|null $sharedthing
	 * @param User|null $author
     */
    public function sendMessage($targets, $content=null, $sharedthing=null, User $author=null)
    {
    	if (!$author) $author = $this->user;
        if (!$author) throw new AccessDeniedException('Access denied');
        
        if ($targets && !is_array($targets)) $targets = array($targets);
        if (!$targets) throw new \Exception('No targets provided');
        
        $share = null;
        if ($sharedthing) $share = $this->createShare($sharedthing);
        
        if (!$share && !$content) throw new \Exception('Must provide at least an entity to share, or a text content, or both');
        
        $message = new Message();
        $message->setAuthor($author);
        $message->setContent($content);
        $message->setShare($share);
        
        foreach ($targets as $target) {
            $mt = new MessageTarget();
            $mt->setTarget($target);
            $message->addMessageTarget($mt);
        }
        
        $this->em->persist($message);
        $this->em->flush();
    }
    
	/**
     * Create a share object for the entity
     * @param mixed $entity
     */
    private function createShare($entity)
    {
        $classname = $this->appstate->getType($entity);
        $share = new Share();
    	$methodname = 'set'.$classname;
    	$share->$methodname($entity);
    	if (property_exists($entity, 'author')) {
    		$share->setAuthor($entity->getAuthor());
    	}
    	
    	return $share;
    }
}