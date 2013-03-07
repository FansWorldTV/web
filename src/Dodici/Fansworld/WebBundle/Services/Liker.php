<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Entity\Activity;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Dodici\Fansworld\WebBundle\Entity\Liking;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Liker
{
    protected $security_context;
    protected $em;
    protected $user;
    protected $userfeedlogger;

    function __construct(SecurityContext $security_context, EntityManager $em, $userfeedlogger)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->userfeedlogger = $userfeedlogger;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Makes $user become a fan of $entity
     * @param Video|Photo|Comment $entity
     * @param User|null $user
     */
    public function like($entity, User $user=null, $flush=true)
    {
    	if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('User not logged in');
        
        $isliking = $this->isLiking($entity, $user);
        $type = $this->getType($entity);
        
        if ($isliking) throw new \Exception('User already likes ' . $type);
        if (!$this->canView($entity, $user)) throw new \Exception('User cannot access ' . $type);
        
        $method = 'set'.ucfirst($type);
        $liking = new Liking();
        $liking->setAuthor($user);
        $liking->$method($entity);
        $this->em->persist($liking);
        $entity->setLikeCount($entity->getLikeCount() + 1);
        $this->em->persist($entity);
        
        if ($entity instanceof Video || $entity instanceof Photo) {
            $this->userfeedlogger->log(Activity::TYPE_LIKED, $entity, $user, false);
        }
        
        $this->em->flush();
    }
    
	/**
     * Makes $user no longer like $entity
     * @param Video|Photo|Comment $entity
     * @param User|null $user
     */
    public function unlike($entity, User $user=null)
    {
    	if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('User not logged in');
        
        $isliking = $this->isLiking($entity, $user);
        $type = $this->getType($entity);
        
        if (!$isliking) throw new \Exception('User does not like ' . $type);
        
        $lc = $entity->getLikeCount();
        foreach ($isliking as $lk) {
    		$this->em->remove($lk);
    		$lc--;
    	} 
    	$this->em->flush();
    	
    	$ent = $this->em->getRepository('DodiciFansworldWebBundle:'.ucfirst($type))->find($entity->getId());
    	$ent->setLikeCount($lc);
    	$this->em->persist($ent);
    	$this->em->flush();
    }
    
	/**
     * Returns whether $user already likes $entity
     * @param $entity
     * @param User|null $user
     */
    public function isLiking($entity, User $user=null)
    {
    	if (!$user) $user = $this->user;
        if (!$user) return false;
        
        $liking = $this->em->getRepository('DodiciFansworldWebBundle:Liking')->byUserAndEntity($user, $entity);
        
        return $liking;
    }
    
    private function getType($entity)
    {
        $name = $this->em->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }
    
    private function canView($entity, $user = null)
    {
        if (!$user) $user = $this->user;

        if (method_exists($entity, 'getActive')) {
            if (!$entity->getActive())
                return false;
        }

        if ($entity instanceof Photo) {
            $album = $entity->getAlbum();
            if ($album && !$album->getActive())
                return false;
        }

        if (property_exists($entity, 'author')) {
            if (($user instanceof User) && ($user == $entity->getAuthor()))
                return true;
        }


        if (method_exists($entity, 'getPrivacy')) {
            if ($entity->getPrivacy() == Privacy::FRIENDS_ONLY) {
                if (!($user instanceof User))
                    return false;
                if (method_exists($entity, 'getAuthor') && $entity->getAuthor()) {
                    if ($user == $entity->getAuthor())
                        return true;
                    $frep = $this->getRepository('DodiciFansworldWebBundle:Friendship');
                    $fr = $frep->findOneBy(array('author' => $user->getId(), 'target' => $entity->getAuthor()->getId(), 'active' => true));

                    if (!$fr)
                        return false;
                }
            }
        }

        return true;
    }
    
}