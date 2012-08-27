<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Idolship;

use Dodici\Fansworld\WebBundle\Entity\Team;

use Dodici\Fansworld\WebBundle\Entity\Idol;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Fanmaker
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

    /**
     * Makes $user become a fan of $entity
     * @param Idol|Team $entity
     * @param User|null $user
     */
    public function addFan($entity, User $user=null)
    {
    	if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        $isfan = $this->isFan($entity, $user);
        
        if ($isfan) throw new \Exception('User is already a fan');
        
        if ($entity instanceof Idol) {
            $idolship = new Idolship();
            $idolship->setAuthor($user);
            $idolship->setIdol($entity);
            $this->em->persist($idolship);
            $this->em->flush();
            return true;
        } elseif ($entity instanceof Team) {
            $teamship = new Teamship();
            $teamship->setAuthor($user);
            $teamship->setTeam($entity);
            $this->em->persist($teamship);
            $this->em->flush();
            return true;
        }
    }
    
	/**
     * Makes $user no longer be a fan of $entity
     * @param Idol|Team $entity
     * @param User|null $user
     */
    public function removeFan($entity, User $user=null)
    {
    	if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        $isfan = $this->isFan($entity, $user);
        
        if (!$isfan) throw new \Exception('User is not a fan');
        
        $this->em->remove($isfan);
        $this->em->flush();
    }
    
	/**
     * Returns whether $user is a fan of $entity
     * @param Idol|Team $entity
     * @param User|null $user
     */
    public function isFan($entity, User $user=null)
    {
    	if (!$user) $user = $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        if ($entity instanceof Idol) {
            return $this->em->getRepository('DodiciFansworldWebBundle:Idolship')->findOneBy(array('author' => $user->getId(), 'idol' => $entity->getId()));
        } elseif ($entity instanceof Team) {
            return $this->em->getRepository('DodiciFansworldWebBundle:Teamship')->findOneBy(array('author' => $user->getId(), 'team' => $entity->getId()));
        } else {
            throw new \Exception('Invalid entity');
        }
    }
    
}