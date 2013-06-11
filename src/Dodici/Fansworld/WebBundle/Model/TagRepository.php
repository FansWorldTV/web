<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;
use Dodici\Fansworld\WebBundle\Entity\VideoCategory;
use Doctrine\ORM\EntityRepository;

/**
 * TagRepository
 */
class TagRepository extends CountBaseRepository
{

    /**
     * Get matching
     * @param string $text
     * @param int|null $limit
     * @param int|null $offset
     */
    public function matching($text = null, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT t
    	FROM \Dodici\Fansworld\WebBundle\Entity\Tag t
    	WHERE
    	(
    	  (:text IS NULL OR (t.title LIKE :textlike))
    	)
    	ORDER BY t.useCount DESC
    	')
                ->setParameter('text', $text)
                ->setParameter('textlike', '%' . $text . '%');

        if ($limit !== null)
            $query = $query->setMaxResults($limit);

        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Returns most popular/latest tags used in videos lately
     * Please use Tagger service if possible
     *
     * @param 'popular'|'latest' $filtertype
     * @param VideoCategory|null $videocategory - filter by video category
     * @param Genre|null $genre - filter by video genre
     * @param int|null $limit
     * @param int|null $offset
     */
    public function usedInVideos($filtertype, $videocategory = null, $genre=null, $limit = null, $offset = null)
    {
        $filtertypes = array('popular', 'latest');

        if (!in_array($filtertype, $filtertypes))
            throw new \InvalidArgumentException('Invalid filter type');

        if ($videocategory && $genre)
            throw new \InvalidArgumentException('VideoCategory and genre are mutually exclusive in this query');

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('usecount', 'count');
        $rsm->addScalarResult('avgweight', 'weight');

        $sqls = array();
        foreach (array('tag', 'idol', 'team') as $type) {
            $sqls[] = '
                SELECT
                ' . $type . '.id as id,
                ' .
                    (($type == 'idol') ?
                            ('CONCAT(' . $type . '.firstname, \' \', ' . $type . '.lastname) AS title,') :
                            ($type . '.title as title,'))
                    . '
                ' . $type . '.slug as slug,
                COUNT(has' . $type . '.id) AS usecount,
                AVG(video.weight) AS avgweight,
                MAX(has' . $type . '.created_at) as latest,
                \'' . $type . '\' as type
                FROM
                has' . $type . '
                INNER JOIN ' . $type . ' ON has' . $type . '.' . $type . '_id = ' . $type . '.id
                INNER JOIN video ON has' . $type . '.video_id = video.id AND video.active = true
                LEFT JOIN genre gn ON gn.id = video.genre_id
                '
                    .(($videocategory) ? ' WHERE (:videocategory IS NULL OR (video.videocategory_id = :videocategory)) ' : '')
                    .(($genre) ? ' WHERE (:genre IS NULL OR (video.genre_id = :genre) OR (gn.parent_id = :genre)) ' : '')
                .' GROUP BY ' . $type . '.id';
        }

        $order = null;
        if ($filtertype == 'popular')
            $order = 'avgweight DESC';
        if ($filtertype == 'latest')
            $order = 'latest DESC';

        $query = $this->_em->createNativeQuery(
                join(' UNION ', $sqls) . '
            ORDER BY
            ' . $order . '
            ' .
                (($limit !== null) ? ' LIMIT :limit ' : '') .
                (($offset !== null) ? ' OFFSET :offset ' : '')
                , $rsm
        );

        if ($videocategory)
            $query = $query->setParameter('videocategory', ($videocategory instanceof VideoCategory) ? $videocategory->getId() : $videocategory, Type::BIGINT);

        if ($genre)
            $query = $query->setParameter('genre', ($genre instanceof Genre) ? $genre->getId() : $genre, Type::BIGINT);

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int) $offset, Type::INTEGER);
        return $query->getResult();
    }

    /**
     * Matches against a string for user/team/idol entities, for autocomplete, etc
     * entities of which the user is a fan
     *
     * @param string $match
     * @param User|null $user
     * @param int|null $limit
     */
    public function matchEntities($match, User $user, $limit = null)
    {

        $results = array();
        $classtypes = array(
            'user' => '\Application\Sonata\UserBundle\Entity\User',
            'idol' => '\Dodici\Fansworld\WebBundle\Entity\Idol',
            'team' => '\Dodici\Fansworld\WebBundle\Entity\Team',
        );
        $likefields = array(
            'user' => array('firstname', 'lastname'),
            'idol' => array('firstname', 'lastname'),
            'team' => array('title'),
        );

        $joins = array(
            'user' =>
            'LEFT JOIN user.friendships uffr WITH uffr.target = :user
                LEFT JOIN user.fanships uffn WITH uffn.author = :user',
            'idol' =>
            'JOIN idol.idolships idshps WITH idshps.author = :user',
            'team' =>
            'JOIN team.teamships tmshps WITH tmshps.author = :user'
        );

        foreach (array('user', 'idol', 'team') as $type) {
            $likes = array();
            foreach ($likefields[$type] as $lf) {
                $likes[] = $type . '.' . $lf . ' LIKE :textlike';
            }

            $dql = '
        	SELECT ' . $type . ', img
        	FROM ' . $classtypes[$type] . ' ' . $type . '
        	LEFT JOIN ' . $type . '.image img
        	' . $joins[$type] . '
        	GROUP BY ' . $type . '
        	HAVING
        	(' . join(' OR ', $likes) . ')
        	' . (($type == 'user') ? '
        		AND (COUNT(uffr) > 0 OR COUNT(uffn) > 0)
        	' : '') . '
        	';

            $query = $this->_em->createQuery($dql)
                    ->setParameter('textlike', '%' . $match . '%')
                    ->setParameter('user', $user->getId());

            if ($limit !== null)
                $query = $query->setMaxResults($limit);

            $results[$type] = $query->getResult();
        }

        return $results;
    }

    /**
     * Matches against a string for user/team/idol/tag entities, for tagging autocomplete, etc
     * all entities found, except for user, which shows only friends
     *
     * @param string $match
     * @param User|null $user
     * @param int|null $limit
     */
    public function matchAll($match, User $user = null, $limit = null)
    {

        $results = array();
        $classtypes = array(
            'user' => '\Application\Sonata\UserBundle\Entity\User',
            'idol' => '\Dodici\Fansworld\WebBundle\Entity\Idol',
            'team' => '\Dodici\Fansworld\WebBundle\Entity\Team',
            'tag' => '\Dodici\Fansworld\WebBundle\Entity\Tag',
        );
        $likefields = array(
            'user' => array('firstname', 'lastname'),
            'idol' => array('firstname', 'lastname'),
            'team' => array('title'),
            'tag' => array('title')
        );
        $orderings = array(
            'user' => array('lastname' => 'ASC', 'firstname' => 'ASC'),
            'idol' => array('lastname' => 'ASC', 'firstname' => 'ASC'),
            'team' => array('fanCount' => 'DESC'),
            'tag' => array('useCount' => 'DESC')
        );

        $joins = array(
            'user' =>
            'LEFT JOIN user.friendships uffr WITH uffr.target = :user
                LEFT JOIN user.fanships uffn WITH uffn.author = :user'
        );

        $types = array();
        if ($user)
            $types[] = 'user';
        $types[] = 'idol';
        $types[] = 'team';
        $types[] = 'tag';

        foreach ($types as $type) {
            $likes = array();
            foreach ($likefields[$type] as $lf) {
                $likes[] = $type . '.' . $lf . ' LIKE :textlike';
            }
            $ordering = array();
            foreach ($orderings[$type] as $fo => $or) {
                $ordering[] = $type . '.' . $fo . ' ' . $or;
            }

            $dql = '
        	SELECT ' . $type . (($type == 'tag') ? '' : ', img') . '
        	FROM ' . $classtypes[$type] . ' ' . $type . '
        	' . (($type == 'tag') ? '' : 'LEFT JOIN ' . $type . '.image img') . '
        	' . (isset($joins[$type]) ? $joins[$type] : '') . '
        	GROUP BY ' . $type . '
        	HAVING
        	(' . join(' OR ', $likes) . ')
        	' . (($type == 'user') ? '
        		AND (COUNT(uffr) > 0 OR COUNT(uffn) > 0)
        	' : '') . '
        	ORDER BY
        	' . join(', ', $ordering) . '
        	';


            $query = $this->_em->createQuery($dql)
                    ->setParameter('textlike', '%' . $match . '%');

            if ($user && $type == 'user')
                $query = $query->setParameter('user', $user->getId());

            if ($limit !== null)
                $query = $query->setMaxResults($limit);



            $results[$type] = $query->getResult();
        }

        return $results;
    }

    /**
     * Returns latest trending tags
     * Please use Tagger service if possible
     *
     * @param int|null $limit
     * @param null|'video'|'photo'|'event' $taggedtype - filter by tagged entity type
     * @param null|'tag'|'idol'|'team' $resulttype - filter by result tag type
     * @param int $daysbefore
     */
    public function trending($limit = 20, $taggedtype = null, $resulttype = null, $daysbefore = 7)
    {
        $taggedtypes = array('video', 'photo', 'event');
        $resulttypes = array('tag', 'idol', 'team');

        if ($taggedtype && !in_array($taggedtype, $taggedtypes))
            throw new \InvalidArgumentException('Invalid tagged type');
        if ($resulttype && !in_array($resulttype, $resulttypes))
            throw new \InvalidArgumentException('Invalid result type');

        $datebefore = new \DateTime('-' . $daysbefore . ' days');

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('usecount', 'count');

        $typearray = array('tag', 'idol', 'team');
        if ($resulttype) {
            $typearray = array($resulttype);
        }

        $sqls = array();
        foreach ($typearray as $type) {
            $sqls[] = '
                SELECT
                ' . $type . '.id as id,
                ' .
                    (($type == 'idol') ?
                            ('CONCAT(' . $type . '.firstname, \' \', ' . $type . '.lastname) AS title,') :
                            ($type . '.title as title,'))
                    . '
                ' . $type . '.slug as slug,
                COUNT(has' . $type . '.id) AS usecount,
                \'' . $type . '\' as type
                FROM
                has' . $type . '
                INNER JOIN ' . $type . ' ON has' . $type . '.' . $type . '_id = ' . $type . '.id
                LEFT JOIN video ON has' . $type . '.video_id = video.id
                LEFT JOIN photo ON has' . $type . '.photo_id = photo.id
                WHERE has' . $type . '.created_at > :datebefore

                AND (has' . $type . '.video_id IS NULL OR video.active = true)
                AND (has' . $type . '.photo_id IS NULL OR photo.active = true)

                ' .
                    ($taggedtype ? '
                	AND has' . $type . '.' . $taggedtype . '_id IS NOT NULL
                ' : '')
                    . '
                GROUP BY ' . $type . '.id
                ';
        }


        $query = $this->_em->createNativeQuery(
                join(' UNION ', $sqls) . '
            ORDER BY
            usecount DESC
            ' .
                (($limit !== null) ? ' LIMIT :limit ' : '')
                , $rsm
        );

        $query = $query->setParameter('datebefore', $datebefore);

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);


        return $query->getResult();
    }

    /**
     * Return tags related to user recommended videos
     *
     * @param User $user
     * @param int|null $limit
     * @param int|null $offset
    */
    public function trendingInRecommended($user, $limit = null, $offset = null)
    {
        if (!$user) throw new \Exception('User parameter is missing');
        $order = 'avgweight DESC';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('usecount', 'count');
        $rsm->addScalarResult('avgweight', 'weight');

        $sqls = array();
        foreach (array('tag', 'idol', 'team') as $type) {

            $sqls[] = '
                SELECT
                ' . $type . '.id as id,
                ' .
                    (($type == 'idol') ?
                            ('CONCAT(' . $type . '.firstname, \' \', ' . $type . '.lastname) AS title,') :
                            ($type . '.title as title,'))
                    . '
                ' . $type . '.slug as slug,
                COUNT(has' . $type . '.id) AS usecount,
                AVG(v.weight) AS avgweight,
                MAX(has' . $type . '.created_at) as latest,
                \'' . $type . '\' as type
                FROM
                has' . $type . '
                INNER JOIN ' . $type . ' ON has' . $type . '.' . $type . '_id = ' . $type . '.id
                INNER JOIN video v

                WHERE

                (v.author_id IN
                    (SELECT frship.target_id FROM friendship frship
                        WHERE frship.author_id = :user)
                )

                OR

                (
                    (v.id IN
                        (
                            (SELECT hteam.video_id FROM hasteam hteam
                             WHERE hteam.team_id IN (SELECT tmship.team_id
                                                    FROM teamship tmship
                                                        WHERE tmship.author_id = :user)
                            )
                        )
                    )
                    AND
                    (v.id IN
                        (
                            (SELECT hidol.video_id FROM hasidol hidol
                             WHERE hidol.idol_id IN (SELECT idlship.idol_id
                                                    FROM idolship idlship
                                                        WHERE idlship.author_id = :user)
                            )
                        )
                    )
                )'
                .
                'GROUP BY ' . $type . '.id';
        }

        $query = $this->_em->createNativeQuery(
                join(' UNION ', $sqls) . '
            ORDER BY
            ' . $order . '
            ' .
                (($limit !== null) ? ' LIMIT :limit ' : '') .
                (($offset !== null) ? ' OFFSET :offset ' : '')
                , $rsm
        );

        $query = $query->setParameter(
            'user', ($user instanceof User) ? $user->getId() : $user, Type::BIGINT
        );

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int) $offset, Type::INTEGER);
        return $query->getResult();
    }


}