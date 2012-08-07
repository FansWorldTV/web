<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\VideoCategory;

use Doctrine\ORM\EntityRepository;

/**
 * VideoCategorySubscriptionRepository
 */
class VideoCategorySubscriptionRepository extends CountBaseRepository
{
    /**
     * Get the users subscribed to a VideoCategory
     * @param VideoCategory $videocategory
     * @param int|null $limit
     * @param int|null $offset
     */
    public function usersSubscribedTo(VideoCategory $videocategory, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT u
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	INNER JOIN u.videocategorysubscriptions uvcs
    	WHERE u.enabled = true AND uvcs.videocategory = :videocategory
    	')
            ->setParameter('videocategory', $videocategory->getId());
        
        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);
        
        return $query->getResult();
    }
}