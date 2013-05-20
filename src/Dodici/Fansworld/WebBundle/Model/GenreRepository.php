<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Event;
use Doctrine\DBAL\Types\Type;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * GenreRepository
 */
class GenreRepository extends CountBaseRepository
{
    public function getChildren($genre)
    {
        $query = $this->_em->createQuery('
            SELECT g FROM \Dodici\Fansworld\WebBundle\Entity\Genre g
            WHERE g.parent = :genre
            ORDER BY g.id ASC
        ')
            ->setParameter('genre', $genre->getId(), Type::BIGINT);

        return $query->getResult();
    }

    public function getParents($limit=null, $offset=null)
    {
        $query = $this->_em->createQuery('
            SELECT g FROM \Dodici\Fansworld\WebBundle\Entity\Genre g
            WHERE g.parent is NULL
            ORDER BY g.id ASC
        ');

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        return $query->getResult();
    }
}