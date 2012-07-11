<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Idol;
use Doctrine\ORM\EntityRepository;

/**
 * IdolshipRepository
 */
class IdolshipRepository extends CountBaseRepository
{

    /**
     * Gets the ranking of users for an idol
     * @param Idol $idol
     * @param int $limit
     * @param int $offset
     */
    public function rankedUsersScore(Idol $idol, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT is, u
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idolship is
    	JOIN is.author u
    	WHERE
    	u.enabled = true AND is.idol = :idol
    	ORDER BY is.score DESC, u.score DESC
    	')
                ->setParameter('idol', $idol->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

}