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
 * ProfileRepository
 */
class ProfileRepository extends CountBaseRepository
{
    /**
     * Returns the Profiles (Idols/Teams) related to $genre(if genre given) order by popularity
     * @param String ('all' | 'idol' | 'team') $filter
     * @param Genre|null or Genre_id(Int)|null  $genre
     * @param int|null $limit
     * @param int|null $offset
     */
    public function search($filter, $genre=null, $limit=null, $offset=null)
    {
        if (!$filter) throw new \Exception('Filter parameter is missing');
        if ('all' != $filter && 'idol' != $filter && 'team' != $filter) throw new \Exception('Invalid filter parameter');
        $order = 'fancount DESC';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('fancount', 'fancount');
        $rsm->addScalarResult('photocount', 'photocount');
        $rsm->addScalarResult('videocount', 'videocount');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('title', 'title');

        $sqls = array();
        foreach (array('idol', 'team') as $type) {
            $sqls[$type] = '
                SELECT ' . $type . '.id as id, ' . $type . '.slug as slug, ' . $type . '.fancount as fancount, "' . $type . '" as type, '.
                $type . '.photocount as photocount, ' . $type . '.videocount as videocount, '
                    .(('idol' == $type) ?
                        ('CONCAT(' . $type . '.firstname, \' \', ' . $type . '.lastname) AS title') : ($type . '.title as title'))
                .' FROM ' . $type . '
                LEFT JOIN genre gen ON gen.id = ' . $type . '.genre_id
                WHERE
                active = true
                AND
                (:genre IS NULL OR (genre_id = :genre OR gen.parent_id = :genre))';
        }

        ('all' == $filter) ? $sql = join(' UNION ', $sqls) : $sql = $sqls[$filter];

        $query = $this->_em->createNativeQuery($sql.' ORDER BY '. $order . '' .
                (($limit !== null) ? ' LIMIT :limit ' : '') .
                (($offset !== null) ? ' OFFSET :offset ' : ''), $rsm
        );

        $query = $query->setParameter(
            'genre', ($genre instanceof Genre) ? $genre->getId() : $genre, Type::BIGINT);

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int) $offset, Type::INTEGER);
        return $query->getResult();
    }
}