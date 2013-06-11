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
     * Returns the Profiles (Idols/Teams) related to $genre(if genre given) OR alls types of profiles, filter by popularity|activity
     * @param String ('all' | 'idol' | 'team') $type
     * @param String ('popular' | 'activity') $filterby
     * @param Genre|null Genre_id(Int)|null  $genre
     * @param int|null $limit
     * @param int|null $offset
     */
    public function latestOrPopular($type, $filterby, $genre=null, $limit=null, $offset=null)
    {
        if (!$type) throw new \Exception('Type parameter is missing');
        if (!$filterby) throw new \Exception('Filterby parameter is missing');
        if ('all' != $type && 'idol' != $type && 'team' != $type) throw new \Exception('Invalid value of type parameter');
        if ('popular' != $filterby && 'activity' != $filterby) throw new \Exception('Invalid value of filterby parameter');

        ('popular' == $filterby) ? $order = 'fancount DESC' : $order = 'activity DESC';
        $datebefore = new \DateTime('-60 days');

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('fancount', 'fancount');
        $rsm->addScalarResult('photocount', 'photocount');
        $rsm->addScalarResult('videocount', 'videocount');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('genre', 'genre');
        $rsm->addScalarResult('imageid', 'imageid');
        if ('activity' == $filterby) $rsm->addScalarResult('activity', 'activity');

        $sqls = array();
        foreach (array('idol', 'team') as $etype) {
            if ('popular' == $filterby) {
                $sqls[$etype] = '
                    SELECT ' . $etype . '.id as id, ' . $etype . '.slug as slug, ' . $etype . '.fancount as fancount, "' . $etype . '" as type, '.
                    $etype . '.photocount as photocount, ' . $etype . '.videocount as videocount, ' . $etype . '.genre_id as genre, '.
                    $etype . '.image_id as imageid, '
                    .(('idol' == $etype) ?
                        ('CONCAT(' . $etype . '.firstname, \' \', ' . $etype . '.lastname) AS title') : ($etype . '.title as title'))
                    .' FROM ' . $etype . '
                    LEFT JOIN genre gen ON gen.id = ' . $etype . '.genre_id
                    WHERE
                    active = true
                    AND
                    (:genre IS NULL OR (genre_id = :genre OR gen.parent_id = :genre))';
            } else {
                $sqls[$etype] = '
                    SELECT v.' . $etype . '_id AS id, COUNT( v.' . $etype . '_id ) AS activity,
                    e.fancount AS fancount, e.videocount AS videocount, e.photocount AS photocount, e.image_id as imageid, '
                    .(('idol' == $etype) ? ('CONCAT(e.firstname, \' \', e.lastname) AS title') : ('e.title as title')).',
                    e.genre_id as genre, e.slug as slug, "' . $etype . '" as type'
                    .' FROM visit v
                    INNER JOIN ' . $etype . ' e ON e.id = v.' . $etype . '_id
                    LEFT JOIN genre gen ON gen.id = e.genre_id
                    WHERE
                    e.active = true
                    AND
                    (:genre IS NULL OR (genre_id = :genre OR gen.parent_id = :genre))
                    AND
                    v.created_at > :datebefore
                    GROUP BY v.' . $etype . '_id
                    HAVING COUNT( v.'. $etype .'_id ) >= 0';
            }
        }

        ('all' == $type) ? $sql = join(' UNION ', $sqls) : $sql = $sqls[$type];

        $query = $this->_em->createNativeQuery($sql.' ORDER BY '. $order . '' .
                (($limit !== null) ? ' LIMIT :limit ' : '') .
                (($offset !== null) ? ' OFFSET :offset ' : ''), $rsm
        );

        $query = $query->setParameter(
            'genre', ($genre instanceof Genre) ? $genre->getId() : $genre, Type::BIGINT);

        if ('activity' == $filterby) $query = $query->setParameter('datebefore', $datebefore);

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int) $offset, Type::INTEGER);
        return $query->getResult();
    }

     /**
     * Return User following profiles
     * @param User|user_id(Int) $user
     * @param int|null $limit
     * @param int|null $offset
     */
    public function followingProfiles($user, $limit=null, $offset=null)
    {
        if (!$user) throw new \Exception('User parameter is missing');

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('target', 'target');
        $rsm->addScalarResult('type', 'type');

        $sql = '
            SELECT target_id AS target, ' . '"user"' . ' AS type
            FROM friendship WHERE author_id = :user AND active = true
            UNION
            SELECT idol_id AS target,  ' . '"idol"' . ' AS type
            FROM idolship WHERE author_id = :user
            UNION
            SELECT team_id AS target,  ' . '"team"' . ' AS type
            FROM teamship WHERE author_id = :user';

        $query = $this->_em->createNativeQuery($sql .
                (($limit !== null) ? ' LIMIT :limit ' : '') .
                (($offset !== null) ? ' OFFSET :offset ' : ''), $rsm
        );

        $query = $query->setParameter(
            'user', ($user instanceof User) ? $user->getId() : $user, Type::BIGINT);

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int) $offset, Type::INTEGER);
        return $query->getResult();
    }
}