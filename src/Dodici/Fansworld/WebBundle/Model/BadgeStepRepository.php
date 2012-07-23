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
    
	/**
     * Returns badgesteps the user has
     * 
     * @return array(
     * 	   'badge' => Badge
     * 	   'steps' => array(
     *     	   BadgeStep,
     *     	   ...
     *     )
     * )
     * 
     * ordered by
	 *     Badge->type ASC,
     *     BadgeStep->minimum ASC
     * 
     * @param User $user
     */
    public function byUser(User $user)
    {
        $query = $this->_em->createQuery('
    	SELECT u, hsb, bs, b
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	JOIN u.hasbadges hsb
    	JOIN hsb.badgestep bs
    	JOIN bs.badge b
    	WHERE
        	u.id = :user
        ORDER BY
        	b.type ASC, bs.minimum ASC
    	')
    	    ->setParameter('user', $user->getId());
    		
    	$resultuser = $query->getOneOrNullResult();
    	
    	if ($resultuser instanceof User) {
    	    $resultarr = array();
    	    // arrange results
    	    $hsbr = $resultuser->getHasbadges();
    	    
    	    foreach ($hsbr as $hsb) {
    	        $badgestep = $hsb->getBadgeStep();
    	        $badge = $badgestep->getBadge();
    	        //$date = $hsb->getCreatedAt();
    	        
    	        if (!isset($resultarr[$badge->getId()])) {
    	            $resultarr[$badge->getId()] = array(
    	                'badge' => $badge,
    	                'steps' => array()
    	            );
    	        }
    	        
    	        $resultarr[$badge->getId()]['steps'][] = $badgestep;
    	    }
    	    
    	    return $resultarr;
    	} else {
    	    return null;
    	}
    }
}