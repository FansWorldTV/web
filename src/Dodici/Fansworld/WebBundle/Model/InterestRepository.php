<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\DBAL\Types\Type;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\InterestCategory;
use Doctrine\ORM\EntityRepository;

/**
 * InterestRepository
 */
class InterestRepository extends CountBaseRepository
{

    /**
     * Get matching
     * @param int|InterestCategory $category
     * @param string $text
     * @param int|User $user
     * @param boolean $excludeuser (exclude the user's current interests)
     * @param int $limit
     * @param int $offset
     */
    public function matching($category = null, $text = null, $user = null, $excludeuser = false, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT i
    	FROM \Dodici\Fansworld\WebBundle\Entity\Interest i
    	WHERE
    	(
    	  (:category IS NULL OR (i.interestcategory = :category))
    	  AND
    	  (:text IS NULL OR (i.title LIKE :textlike))
    	  AND
    	  (:user IS NULL OR (
    	  	((SELECT COUNT(hi.id) FROM \Dodici\Fansworld\WebBundle\Entity\HasInterest hi WHERE hi.interest = i.id AND hi.author = :user) ' . ($excludeuser ? '<' : '>=') . ' 1)
    	  ))
    	)
    	')
                ->setParameter('category', ($category instanceof InterestCategory) ? $category->getId() : $category)
                ->setParameter('text', $text)
                ->setParameter('user', ($category instanceof User) ? $user->getId() : $user)
                ->setParameter('textlike', '%' . $text . '%');

        if ($limit !== null)
            $query = $query->setMaxResults($limit);

        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

}