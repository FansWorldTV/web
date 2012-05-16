<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;

/**
 * ComplaintRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ComplaintRepository extends CountBaseRepository
{

    /**
     * Has the user already complained?
     */
    public function UserAndEntity(\Application\Sonata\UserBundle\Entity\User $user, $entity)
    {
        if ($entity instanceof \Application\Sonata\UserBundle\Entity\User) {
            return false;
        }

        $exp = explode('\\', get_class($entity));
        $classname = strtolower(end($exp));
        if (strpos($classname, 'proxy') !== false) {
            $classname = str_replace(array('dodicifansworldwebbundleentity', 'proxy'), array('', ''), $classname);
        }

        return $this->findBy(array('author' => $user->getId(), $classname => $entity->getId()));
    }

    public function getByEntity($limit = null, $offset = null, array $groupBy = null)
    {
        if (!$groupBy) {
            $query = $this->_em->createQuery('
                SELECT c 
                FROM \Dodici\Fansworld\WebBundle\Entity\Complaint c
                GROUP BY c.video, c.photo, c.comment
            ');
        } else {
            $query = $this->_em->createQuery('
                SELECT c 
                FROM \Dodici\Fansworld\WebBundle\Entity\Complaint c
                GROUP BY :groups
            ');
            $groups = implode(', ', $groupBy);
            $query->setParameter('groups', $groups);
        }

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);
        
        return $query->getResult();
    }
}