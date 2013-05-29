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
    public function getActives($genre=null, $category=null)
    {
        if (!$genre && !$category) throw new \Exception('You must provide a genre or category');

        if ($genre) {
            $dql = '
                SELECT g FROM \Dodici\Fansworld\WebBundle\Entity\Genre g

                    WHERE
                        (g.parent = :genre)
                        AND
                        (g.id IN (SELECT vg FROM \Dodici\Fansworld\WebBundle\Entity\Video v JOIN v.genre vg WHERE vg = g.id))
                        ORDER BY g.id ASC';
        } else {
            $dql = '
                SELECT g FROM \Dodici\Fansworld\WebBundle\Entity\Genre g
                    WHERE
                        (g.id IN (SELECT vg FROM \Dodici\Fansworld\WebBundle\Entity\Video v JOIN v.videocategory vc JOIN v.genre vg WHERE vc = :cat))
                        ORDER BY g.id ASC';
        }

        $query = $this->_em->createQuery($dql);
        if ($genre) $query->setParameter('genre', ($genre instanceof Genre) ? $genre->getId() : $genre, Type::BIGINT);
        if ($category) $query->setParameter('cat', ($category instanceof VideoCategory) ? $category->getId() : $category, Type::BIGINT);
        return $query->getResult();
    }

    public function getParents($limit=null, $offset=null)
    {
        $dql = '
            SELECT g FROM \Dodici\Fansworld\WebBundle\Entity\Genre g
                WHERE g.parent is NULL ORDER BY g.id ASC';

        $query = $this->_em->createQuery($dql);
        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);
        return $query->getResult();
    }
}