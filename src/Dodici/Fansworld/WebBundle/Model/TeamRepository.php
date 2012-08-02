<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

/**
 * TeamRepository
 */
class TeamRepository extends CountBaseRepository
{
    /**
     * Get matching
     * @param TeamCategory|null $category
     * @param string|null $text
     * @param int|null $limit
     * @param int|null $offset
     */
    public function matching($category = null, $text = null, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT t
    	FROM \Dodici\Fansworld\WebBundle\Entity\Team t
                  INNER JOIN t.teamcategories tc
    	WHERE tc.id=:category AND t.title LIKE :textlike
    	')
                ->setParameter('category', $category)
                ->setParameter('textlike', '%' . $text . '%');

        if ($limit !== null)
            $query = $query->setMaxResults($limit);

        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

}