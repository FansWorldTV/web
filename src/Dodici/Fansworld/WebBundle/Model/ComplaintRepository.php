<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;

/**
 * ComplaintRepository
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

        $classname = $this->getType($entity);

        return $this->findBy(array('author' => $user->getId(), $classname => $entity->getId()));
    }

    /**
     * Get the grouped counts of complaints
     * @param int $limit
     * @param int $offset
     * @param array $groupBy
     */
    public function getByEntity($limit = null, $offset = null, array $groupBy = null)
    {
        if (!$groupBy || count($groupBy) < 1) {
            $query = $this->_em->createQuery('
                SELECT c 
                FROM \Dodici\Fansworld\WebBundle\Entity\Complaint c
                GROUP BY c.video, c.photo, c.comment
            ');
        } else {
            
            $validGroups = array(
                'video',
                'photo',
                'comment'
            );

            $valid = false;
            foreach ($validGroups as $group) {
                $valid = in_array($group, $groupBy);
                if($valid)
                    break;
            }
            
            if (!$valid)
                return false;

            if (count($groupBy) > 1) {
                $groups = implode(', c.', $groupBy);
                $groups = "c." . $groups;
                
                $where = implode(' IS NOT null OR c.', $groupBy);
                $where = 'c.' . $where . ' IS NOT null';
            } else {
                $groups = "c." . $groupBy[0];
                $where = "c.".$groupBy[0] . " IS NOT null";
            }
            
            $query = $this->_em->createQuery('
                SELECT c 
                FROM \Dodici\Fansworld\WebBundle\Entity\Complaint c
                WHERE '.$where.' 
                GROUP BY ' . $groups . '
            ');
        }
        
        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        return $query->getResult();
    }

}