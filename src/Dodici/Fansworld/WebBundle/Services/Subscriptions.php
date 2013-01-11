<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\VideoCategorySubscription;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Dodici\Fansworld\WebBundle\Entity\VideoCategory;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Handles user subscriptions: to videocategories, etc
 */
class Subscriptions
{
	protected $security_context;
	protected $em;
    protected $user;
    
    function __construct(SecurityContext $security_context, EntityManager $em)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    
    public function subscribe($entity, User $user=null)
    {
        if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');

        if ($entity instanceof VideoCategory && !($this->isSubscribed($entity, $user))) {
            $user->subscribeVideoCategory($entity);
            $this->em->persist($user);
            $this->em->flush();
            return true;
        }
        
        return false;
    }   
     
    public function unsubscribe($entity, User $user=null)
    {
        if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        if ($entity instanceof VideoCategory) {
            $vc = $user->unsubscribeVideoCategory($entity);
            if ($vc) {
                $this->em->remove($vc);
                $this->em->flush();
                return true;
            }
        }
        
        return false;
    }
    
    public function usersSubscribed(VideoCategory $videocategory)
    {
        return 
            $this->em->getRepository('DodiciFansworldWebBundle:VideoCategorySubscription')
            ->usersSubscribedTo($videocategory);
    }
    
    public function isSubscribed(VideoCategory $videocategory, User $user=null)
    {
        if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        return 
            $this->em->getRepository('DodiciFansworldWebBundle:VideoCategorySubscription')
            ->findOneBy(array('author' => $user->getId(), 'videocategory' => $videocategory->getId()));
    }
    
    public function get(User $user=null, $limit=null, $offset=null)
    {
        if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        return 
            $this->em->getRepository('DodiciFansworldWebBundle:VideoCategorySubscription')
            ->findBy(array('author' => $user->getId()), null, $limit, $offset);
    }
}