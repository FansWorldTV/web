<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

/**
 * BadgeStepRepository
 */
class BadgeStepRepository extends CountBaseRepository
{
    /**
     * Returns badgesteps we need to give to the user, according to the badge type and the amount achieved
     * 
     * @param User $user
     * @param Badge::TYPE_* $type
     * @param int $amount
     */
    public function toGive(User $user, $type, $amount)
    {
        $query = $this->_em->createQuery('
    	SELECT bs, b
    	FROM \Dodici\Fansworld\WebBundle\Entity\BadgeStep bs
    	JOIN bs.badge b
    	WHERE
        	b.type = :type
        	AND
        	bs.minimum <= :amount
        	AND
        	bs.id NOT IN (SELECT bshh.id FROM \Dodici\Fansworld\WebBundle\Entity\HasBadge hsb JOIN hsb.badgestep bshh WHERE hsb.author = :user)
    	')
    	    ->setParameter('user', $user->getId())	
            ->setParameter('type', $type)
    		->setParameter('amount', $amount);
    		
    	return $query->getResult();
    }
}