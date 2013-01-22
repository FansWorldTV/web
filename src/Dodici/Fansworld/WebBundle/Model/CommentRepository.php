<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Event;
use Doctrine\DBAL\Types\Type;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * CommentRepository
 */
class CommentRepository extends CountBaseRepository
{

    /**
     * Retrieve the Comments for an entity
     * @param mixed $entity - the entity to which the comments belong
     * @param User $user - the user viewing the comments
     * @param int $lastId - the oldest id to start retrieving from
     * @param int $limit
     * @param int $offset
     */
    public function wallEntity($entity, $user = null, $lastId = null, $limit = null, $offset = null)
    {
        if ($entity instanceof \Application\Sonata\UserBundle\Entity\User) {
            $classname = 'target';
        } else {
            $classname = $this->getType($entity);
        }


        $query = $this->_em->createQuery('
    	SELECT c
    	FROM \Dodici\Fansworld\WebBundle\Entity\Comment c
    	LEFT JOIN c.author ca
    	WHERE c.' . $classname . ' = :entity 
    	AND c.active = true
    	'.
        (($classname != 'comment') ? 'AND c.comment IS NULL' : '')
        .'
    	AND (:lastId IS NULL OR ( c.id < :lastId ))
    	' .
                        (($classname == 'team') ? ' AND c.event IS NULL ' : '')
                        . '
    	AND
    	(
    		(c.author = :user)
    		OR
    		(c.privacy = :everyone)
    		OR
	    	((c.privacy = :friendsonly) AND (:user IS NOT NULL) AND (
	    		(SELECT COUNT(iss.id) FROM \Dodici\Fansworld\WebBundle\Entity\Friendship iss WHERE (iss.author = :user AND iss.target = ca.id AND iss.active = true)) >= 1
            ))
    	)
    	    	
    	ORDER BY c.id DESC
    	')
                ->setParameter('entity', $entity->getId(), Type::BIGINT)
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('lastId', $lastId);

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        return $query->getResult();
    }

    public function getCommentSubcomments($entity)
    {
        $classname = $this->getType($entity);

        $query = $this->_em->createQuery('
            SELECT c FROM \Dodici\Fansworld\WebBundle\Entity\Comment c
            WHERE c.' . $classname . ' = :entity 
            ORDER BY c.id ASC
    	')
                ->setParameter('entity', $entity->getId(), Type::BIGINT);

        return $query->getResult();
    }

    /**
     * Get comments for event wall
     * @param Event|int $event
     * @param DateTime|null $maxdate
     * @param DateTime|null $mindate
     */
    public function eventWall($event, $maxdate = null, $mindate = null)
    {
        $dql = '
		SELECT c, a, t, ai
    	FROM \Dodici\Fansworld\WebBundle\Entity\Comment c
    	LEFT JOIN c.team t
    	LEFT JOIN c.author a
		LEFT JOIN a.image ai
		WHERE c.event = :event
		';

        if ($maxdate)
            $dql .= ' AND c.createdAt < :maxdate ';
        if ($mindate)
            $dql .= ' AND c.createdAt >= :mindate ';

        $dql .= ' ORDER BY c.createdAt DESC';

        $query = $this->_em->createQuery($dql)
                ->setParameter('event', ($event instanceof Event) ? $event->getId() : $event);

        if ($maxdate)
            $query = $query->setParameter('maxdate', $maxdate);
        if ($mindate)
            $query = $query->setParameter('mindate', $mindate);

        return $query->getResult();
    }

}