<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;
use Application\Sonata\UserBundle\Entity\User;

/**
 * FriendshipRepository
 */
class FriendshipRepository extends CountBaseRepository {

    /**
     * Get friendship object between two users
     */
    public function betweenUsers(User $author, User $target) {
        return $this->_em->createQuery('
    	SELECT fs
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	WHERE
    	(fs.author = :userone) AND (fs.target = :usertwo)
    	')
                        ->setParameter('userone', $author->getId(), Type::BIGINT)
                        ->setParameter('usertwo', $target->getId(), Type::BIGINT)
                        ->getOneOrNullResult();
    }

    /**
     * Get pending friendship requests for user
     */
    public function pending(User $user, $limit = null, $offset = null) {
        $query = $this->_em->createQuery('
    	SELECT fs, a
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	JOIN fs.author a
    	WHERE
    	fs.target = :userid AND fs.active=false
    	')
                ->setParameter('userid', $user->getId(), Type::BIGINT);

        if ($limit !== null)
            $query = $query->setMaxResults($limit);

        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Count pending friendships for user
     */
    public function countPending(User $user) {
        $query = $this->_em->createQuery('
    	SELECT COUNT(fs.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	WHERE
    	fs.target = :userid AND fs.active=false
    	')
                ->setParameter('userid', $user->getId(), Type::BIGINT);

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Get whether the users are friends or not
     * @param User $userone
     * @param User $usertwo
     */
    public function usersAreFriends(User $userone, User $usertwo) {
        $qb = $this->_em->createQueryBuilder();
        $qb
                ->add('select', 'f')
                ->add('from', $this->_entityName . ' f')
                ->where('( ( f.author = ?1 AND f.target = ?2 ) OR ( f.author = ?2 AND f.target = ?2 ) ) AND f.active = true')
                ->setMaxResults(1)
                ->setParameter(1, $userone)
                ->setParameter(2, $usertwo);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

}