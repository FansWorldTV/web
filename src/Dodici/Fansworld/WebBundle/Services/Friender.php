<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Activity;
use Symfony\Component\Security\Core\SecurityContext;
use Dodici\Fansworld\WebBundle\Listener\ScoreHandler;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Notification;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Friender
{
    protected $security_context;
    protected $em;
    protected $appstate;
    protected $user;
    protected $userfeedlogger;

    function __construct(SecurityContext $security_context, EntityManager $em, $appstate, $userfeedlogger)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->appstate = $appstate;
        $this->userfeedlogger = $userfeedlogger;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Makes $author follow $target
     * @param User $target
     * @param array|null $friendgroups
     * @param User|null $author
     * @param boolean $invitation
     */
    public function friend(User $target, $friendgroups=null, User $author=null, $invitation=false)
    {
    	if (!$author) $author = $this->user;
        if (!$author) throw new AccessDeniedException('Access denied');
        
        if (!$this->appstate->canFriend($target, $author))
            throw new \Exception('Cannot follow this user');
        
        $friendship = new Friendship();
        $friendship->setAuthor($author);
        $friendship->setTarget($target);
        
        if ($invitation) {
            $friendship->setActive(true);
            $friendship->setInvitation(true);
        } elseif ($target->getRestricted()) {
            $friendship->setActive(false);
        }
        
        if ($friendgroups) {
            foreach ($friendgroups as $friendgroup) {
                $friendship->addFriendGroup($friendgroup);
            }
        }
        
        $this->scoreAdd($friendship);
        
        $this->em->persist($friendship);
        
        // notify became fan or pending
        $notification = new Notification();
        if ($friendship->getActive()) {
		    $notification->setType(Notification::TYPE_FRIENDSHIP_NEW_FAN);
		    $this->userfeedlogger->log(Activity::TYPE_BECAME_FAN, $friendship->getTarget(), $friendship->getAuthor(), false);
        } else {
            $notification->setType(Notification::TYPE_FRIENDSHIP_PENDING);
            $notification->setFriendship($friendship);
        }
		
		$notification->setAuthor($friendship->getAuthor());
		$notification->setTarget($friendship->getTarget());
		$this->em->persist($notification);
		
        $this->em->flush();
        
        // notify new?
        
        return $friendship;
    }
    
	/**
     * Accept a friendship
     * @param Friendship $friendship
     */
    public function accept(Friendship $friendship)
    {
    	if (!$this->user) throw new AccessDeniedException('Not logged on');
        if ($this->user != $friendship->getTarget() && $this->user->getType() != User::TYPE_STAFF)
            throw new AccessDeniedException('Access denied');
        
        if ($friendship->getActive()) return false;
        
        $friendship->setActive(true);
        
        $this->notifyAccept($friendship);
        $this->scoreAdd($friendship);
        
        $this->em->persist($friendship);
        $this->em->flush();
    }
    
	/**
     * Removes a friendship
     * @param Friendship|User $target
     * @param User|null $author
     */
    public function remove($target, User $author=null)
    {
    	if (!$author) $author = $this->user;
        if (!$author) throw new AccessDeniedException('Not logged on');
        
        if ($target instanceof Friendship) $friendship = $target;
        elseif ($target instanceof User) {
            $frrepo = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
            $friendship = $frrepo->findOneBy(array('author' => $author->getId(), 'target' => $target->getId()));
            if (!$friendship) throw new \Exception('User is not fan of target');
        }
        else throw new \Exception('Friendship remove target is not instance of User or Friendship');
        
        if ($author->getType() != User::TYPE_STAFF && ($author != $friendship->getAuthor() && $author != $friendship->getTarget()))
            throw new AccessDeniedException('Access denied');
        
        $this->scoreRemove($friendship);
            
        $this->markPendingAsRead($friendship, true);
        $this->em->remove($friendship);
        
        $this->em->flush();
    }
    
    private function notifyAccept(Friendship $friendship)
    {
        // notif: carlitos aceptó tu solicitud de amistad...
        $notification = new Notification();
		$notification->setType(Notification::TYPE_FRIENDSHIP_ACCEPTED);
		$notification->setAuthor($friendship->getTarget());
		$notification->setTarget($friendship->getAuthor());
		$this->em->persist($notification);
		
		$this->userfeedlogger->log(Activity::TYPE_BECAME_FAN, $friendship->getTarget(), $friendship->getAuthor(), false);
		
		$this->markPendingAsRead($friendship);
		
		$this->newFanNotification($friendship);
    }
    
    private function markPendingAsRead(Friendship $friendship, $remove=false)
    {
        $notifrepo = $this->em->getRepository('DodiciFansworldWebBundle:Notification');
		
		// mark pending notification as read
		$notifpending = $notifrepo->findBy(array(
		    'type' => Notification::TYPE_FRIENDSHIP_PENDING,
		    'friendship' => $friendship->getId()
		));
		foreach ($notifpending as $np) {
		    $np->setReaded(true);
		    if ($remove) $np->setFriendship(null);
		    $this->em->persist($np);
		}
    }
    
    private function newFanNotification(Friendship $friendship)
    {
        // create new fan notification, might want to consider it coming as pre-read
		$notifnew = new Notification();
		$notifnew->setType(Notification::TYPE_FRIENDSHIP_NEW_FAN);
		$notifnew->setAuthor($friendship->getAuthor());
		$notifnew->setTarget($friendship->getTarget());
		$this->em->persist($notifnew);
    }
    
    private function scoreAdd(Friendship $friendship, $remove=false)
    {
        $scoreadd = ScoreHandler::SCORE_NEW_FRIENDSHIP;
		if ($friendship->getInvitation() && !$remove) $scoreadd += ScoreHandler::SCORE_INVITE_FRIEND;
        $this->addScoreToUser($friendship->getTarget(), $remove ? -$scoreadd : $scoreadd);
        
        $target = $friendship->getTarget(); 
        $fancount = $this->em->getRepository('DodiciFansworldWebBundle:Friendship')->countBy(array(
            'target' => $target->getId(),
            'active' => true
        ));
        if ($remove) $fancount--;
        else $fancount++;
        $target->setFanCount($fancount);
        $this->em->persist($target);
    }
        
    private function scoreRemove(Friendship $friendship)
    {
        $this->scoreAdd($friendship, true);
    }
    
    private function addScoreToUser(User $user, $score)
    {
    	$user->setScore($user->getScore() + $score);
    	if ($user->getScore() < 0) $user->setScore(0);
    	
    	$level = $this->em->getRepository('DodiciFansworldWebBundle:Level')->byScore($user->getScore());
        
    	if ($level) {
	    	if ($user->getLevel() == null || $user->getLevel()->getId() != $level->getId()) {
	        	$user->setLevel($level);
	        }
	    	
	    	$this->em->persist($user);
    	}
    }
}