<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Event;
use Doctrine\DBAL\Types\Type;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

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
    
    public function byVideoCategory($vc, $limit=null, $offset=null)
    {

        if(!$vc) throw new \Exception('Video Category parameter is missing');

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('title', 'title');
        $sql = '
            SELECT g.id as id, g.title as title FROM genre g
                INNER JOIN video v ON v.genre_id = g.id
                WHERE v.videocategory_id = :vc
                GROUP BY g.id';
        $query = $this->_em->createNativeQuery($sql .
            (($limit !== null) ? ' LIMIT :limit ' : '') .
            (($offset !== null) ? ' OFFSET :offset ' : ''), $rsm
        );

        $query = $query->setParameter('vc', $vc, Type::BIGINT);

        if($limit !== null)
            $query = $query->setParameter('limit', (int)$limit, Type::INTEGER);
        if($offset !== null)
            $query = $query->setParameter('offset', (int)$offset, Type::INTEGER);

        return $query->getResult();
    }
}