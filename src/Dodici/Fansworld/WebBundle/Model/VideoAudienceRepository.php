<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;

use Dodici\Fansworld\WebBundle\Entity\Video;

use Doctrine\ORM\EntityRepository;

/**
 * VideoAudienceRepository
 *
 * Video audiences - who's watching the video now
 */
class VideoAudienceRepository extends CountBaseRepository
{
	/**
	 * Get users watching a video
	 * @param Video $video
	 * @param User|null $user
	 */
    public function watching(Video $video, User $user=null)
	{
		$query = $this->_em->createQuery('
    	SELECT va, u
    	FROM \Dodici\Fansworld\WebBundle\Entity\VideoAudience va
    	INNER JOIN va.author u
    	WHERE
    	va.video = :video
    	'.
		($user ? '
		AND va.author <> :user
		' : '')
		.'
    	ORDER BY va.updatedAt DESC
    	')
		->setParameter('video', $video->getId());
		
		if ($user) $query = $query->setParameter('user', $user->getId());
		
		$users = array();
		$res = $query->getResult();
    	foreach ($res as $r) $users[$r->getAuthor()->getId()] = $r->getAuthor();
    	
    	return $users;
	}
	
	/**
	 * Get videoaudiences that are no longer watching a video, timed out
	 * @param DateTime $datefrom
	 */
    public function timedOut(\DateTime $datefrom)
	{
		$query = $this->_em->createQuery('
    	SELECT va, u, v
    	FROM \Dodici\Fansworld\WebBundle\Entity\VideoAudience va
    	INNER JOIN va.author u
    	INNER JOIN va.video v
    	WHERE
    	va.updatedAt < :datefrom
    	')
		->setParameter('datefrom', $datefrom);
		
    	return $query->getResult();
	}
}