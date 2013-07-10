<?php

namespace Dodici\Fansworld\WebBundle\Model;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Dodici\Fansworld\WebBundle\Entity\VideoCategory;
use Dodici\Fansworld\WebBundle\Entity\Genre;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Entity\Tag;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * VideoRepository
 */
class VideoRepository extends CountBaseRepository
{

    /**
     * Search videos by text/tag, visible to the user
     *
     * @param string|Tag|null $searchterm
     * @param User|null $user
     * @param int|null $limit
     * @param int|null $offset
     * @param VideoCategory|null $category
     * @param boolean|null $highlighted
     * @param User|false|null $author (false = site videos)
     * @param DateTime|null $datefrom
     * @param DateTime|null $dateto
     * @param 'default'(weight)|'views'|'likes'|null $sortcriteria
     * @param User|Idol|Team|null $taggedentity
     * @param array<Video|int>|Video|int|null $excludes
     * @param Video|null $related
     * @param boolean|null $recommended - if true, show videos recommended via followed team/idols to user
     * @param 'ASC'|'DESC'|null $sortorder
     * @param string|Tag|null $tag - Tag slug, or entity, to search by
     * @param Genre | null | $genre
     */
    public function search(
        $searchterm = null,
        $user = null,
        $limit = null,
        $offset = null,
        $category = null,
        $highlighted = null,
        $author = null,
        $datefrom = null,
        $dateto = null,
        $sortcriteria = null,
        $taggedentity = null,
        $excludes = null,
        $related = null,
        $recommended = null,
        $sortorder = null,
        $tag = null,
        $genre = null
    )
    {
        if ($recommended && !$user) throw new \Exception('You must provide a user to get recommended videos');
        if ($recommended && $related) throw new \Exception('Related and recommended are mutually exclusive');

        $terms = array();
        $xp = explode(' ', $searchterm);
        foreach ($xp as $x) if (trim($x)) $terms[] = trim($x);

        if(!$sortcriteria)
        {
            $sortcriteria = 'default';
        }

        if (!$sortorder) $sortorder = 'DESC';

        $sortcriterias = array(
            'default' => 'v.weight '.$sortorder,
            'views' => 'v.viewCount '.$sortorder,
            'likes' => 'v.likeCount '.$sortorder,
            'date' => 'v.createdAt '.$sortorder
        );

        if ($taggedentity) {
            $type = $this->getType($taggedentity);
        }

        $excludeids = array();
        if ($excludes) {
            if (!is_array($excludes)) $excludes = array($excludes);

            foreach ($excludes as $exc) {
                if ($exc instanceof Video) $excludeids[] = $exc->getId();
                elseif (is_integer($exc)) $excludeids[] = $exc;
                else throw new \Exception('Invalid $excludes value');
            }
        }

        $dql = '
    	SELECT v, vi, va '.

        ($related ? ', (COUNT(vhtag) + COUNT(vhteam) + COUNT(vhidol)) common' : '') .
        ($recommended ? ', (COUNT(vhrecteam) + COUNT(vhrecidol)) commonrec' : '')
        .'
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.author va
    	LEFT JOIN v.image vi
        LEFT JOIN v.genre vg
    	'.

        ($taggedentity ? ' INNER JOIN v.has' . $type . 's vhh ' : '').

        ($related ? '
        LEFT JOIN v.hastags vhtag
			WITH (vhtag.tag IN (SELECT bshtag.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTag hsbtag JOIN hsbtag.tag bshtag WHERE hsbtag.video = :related))
		LEFT JOIN v.hasteams vhteam
			WITH (vhteam.team IN (SELECT bshteam.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTeam hsbteam JOIN hsbteam.team bshteam WHERE hsbteam.video = :related))
		LEFT JOIN v.hasidols vhidol
			WITH (vhidol.idol IN (SELECT bshidol.id FROM \Dodici\Fansworld\WebBundle\Entity\HasIdol hsbidol JOIN hsbidol.idol bshidol WHERE hsbidol.video = :related))
        ' : '')

        .

        ($recommended ? '
        LEFT JOIN v.hasteams vhrecteam
            WITH (vhrecteam.team IN (SELECT recishteam.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship rectship JOIN rectship.team recishteam WHERE rectship.author = :user))
        LEFT JOIN v.hasidols vhrecidol
            WITH (vhrecidol.idol IN (SELECT recishidol.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship reciship JOIN reciship.idol recishidol WHERE reciship.author = :user))
        ' : '')

        .

        ($tag ? '
        JOIN v.hastags vsrchht
    	JOIN vsrchht.tag vsrchhtag WITH '.
    	(($tag instanceof Tag) ? '
    		vsrchhtag = :tag
    	' : '
    		vsrchhtag.slug = :tag
    	') : '')

    	.'
    	WHERE v.active = true
    	'.
    	($taggedentity ? ('AND vhh.' . (($type == 'user') ? 'target' : $type) . ' = :taggedentity ') : '');

    	if ($terms) {

    	    foreach ($terms as $k => $t) {
            	$dql .= '
            	AND
            	(
            		(v.title LIKE :term'.$k.')
            		OR
            		(v.content LIKE :term'.$k.')
            		OR
            		(v.id IN (SELECT vhtvtx'.$k.'.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTag vhttx'.$k.' INNER JOIN vhttx'.$k.'.video vhtvtx'.$k.' INNER JOIN vhttx'.$k.'.tag vhtttx'.$k.' WITH vhtttx'.$k.'.title LIKE :term'.$k.'))
            		OR
            		(v.id IN (SELECT vhtvmx'.$k.'.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTeam vhtmx'.$k.' INNER JOIN vhtmx'.$k.'.video vhtvmx'.$k.' INNER JOIN vhtmx'.$k.'.team vhttmx'.$k.' WITH vhttmx'.$k.'.title LIKE :term'.$k.'))
            		OR
            		(v.id IN (SELECT vhtvix'.$k.'.id FROM \Dodici\Fansworld\WebBundle\Entity\HasIdol vhtix'.$k.' INNER JOIN vhtix'.$k.'.video vhtvix'.$k.' INNER JOIN vhtix'.$k.'.idol vhttix'.$k.' WITH (vhttix'.$k.'.firstname LIKE :term'.$k.' OR vhttix'.$k.'.lastname LIKE :term'.$k.')))
            	)
            	';
    	    }

    	}


    	$dql .= '
    	AND
    	(
    		(v.privacy = :everyone)
    		OR
	    	(v.privacy = :friendsonly AND (:user IS NOT NULL) AND (
	    		(:user = v.author) OR
	    		((SELECT COUNT(f.id) FROM \Dodici\Fansworld\WebBundle\Entity\Friendship f WHERE (f.target = v.author AND f.author = :user) AND f.active=true) >= 1)
	    	))
    	)
    	AND
    	(
    		(:category IS NULL OR
    			(
    				(:category = false AND v.videocategory IS NULL)
    				OR
    				(:category <> false AND v.videocategory = :category)
    			)
    		)
    	)
    	AND
    	(
    		(:highlighted IS NULL OR
    			(v.highlight = :highlighted)
    		)
    	)
        AND
        (
            (:genre IS NULL OR
                (
                    (:genre <> false AND
                        (vg = :genre OR vg.parent = :genre)
                    )
                )
            )
        )
    	'.
    	(($author !== null) ?
    	(
    	    ' AND ' .
    	    (($author === false) ? ' v.author IS NULL ' :
    	    ' v.author = :author ')
    	) : '')
    	.'
    	AND
    	( :datefrom IS NULL OR (v.createdAt >= :datefrom) )
    	AND
    	( :dateto IS NULL OR (v.createdAt <= :dateto) )
    	'.
    	($excludeids ? '
    	AND (v.id NOT IN (:excludeids))
    	' : '')
    	.'

    	'.
    	($related ? '
    	GROUP BY v
        HAVING
        common > 0
    	' : '')

    	.
    	($recommended ? '
    	GROUP BY v
    	HAVING
    	commonrec > 0
    	' : '')

    	.'

    	ORDER BY

    	' . ($related ? 'common DESC, ' : '') . '
    	' . ($recommended ? 'commonrec DESC, ' : '') . '
    	' . $sortcriterias[$sortcriteria] . '

    	';

    	$query = $this->_em->createQuery($dql)
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : $user)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('datefrom', $datefrom)
                ->setParameter('dateto', $dateto)
                ->setParameter('highlighted', $highlighted)
                ->setParameter('genre', ($genre instanceof Genre) ? $genre->getId() : $genre);

        if ($terms) foreach ($terms as $k => $t) $query = $query->setParameter('term'.$k, '%' . $t . '%');

        if ($author)
            $query = $query->setParameter('author', ($author instanceof User) ? $author->getId() : $author);

        if ($taggedentity)
            $query = $query->setParameter('taggedentity', $taggedentity->getId());

        if ($related)
            $query = $query->setParameter('related', $related->getId());

        if ($excludeids)
            $query = $query->setParameter('excludeids', $excludeids);

        if ($tag)
            $query = $query->setParameter('tag', ($tag instanceof Tag) ? $tag->getId() : $tag);

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        $res = $query->getResult();

        if ($related || $recommended) {
            $res = $query->getResult();
            $arr = array();
            foreach ($res as $r) $arr[] = $r[0];
            return $arr;
        } else {
            return $res;
        }
    }

    /**
     * Count videos by text/tag, visible to the user
     *
     * @param string|Tag|null $searchterm
     * @param User|null $user
     * @param VideoCategory|null $category
     * @param boolean|null $highlighted
     * @param User|null $author
     * @param DateTime|null $datefrom
     * @param DateTime|null $dateto
     * @param User|Idol|Team|null $taggedentity
     * @param array<Video|int>|Video|int|null $excludes
     * @param Video|null $related
     * @param boolean|null $recommended - if true, show videos recommended via followed team/idols to user
     * @param string|Tag|null $tag - Tag slug, or entity, to search by
     */
    public function countSearch(
        $searchterm = null,
        $user = null,
        $category = null,
        $highlighted = null,
        $author = null,
        $datefrom = null,
        $dateto = null,
        $taggedentity = null,
        $excludes = null,
        $related = null,
        $recommended = null,
        $tag = null,
        $genre = null
    )
    {

        if ($recommended && !$user) throw new \Exception('You must provide a user to get recommended videos');
        if ($recommended && $related) throw new \Exception('Related and recommended are mutually exclusive');

        $terms = array();
        $xp = explode(' ', $searchterm);
        foreach ($xp as $x) if (trim($x)) $terms[] = trim($x);

        if ($taggedentity) {
            $type = $this->getType($taggedentity);
        }

        $excludeids = array();
        if ($excludes) {
            if (!is_array($excludes)) $excludes = array($excludes);

            foreach ($excludes as $exc) {
                if ($exc instanceof Video) $excludeids[] = $exc->getId();
                elseif (is_integer($exc)) $excludeids[] = $exc;
                else throw new \Exception('Invalid $excludes value');
            }
        }

        $dql = '
    	SELECT COUNT(v.id)'

        .'
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	'.
    	($taggedentity ? ' INNER JOIN v.has' . $type . 's vhh ' : '') .



        ($tag ? '
        JOIN v.hastags vsrchht
    	JOIN vsrchht.tag vsrchhtag WITH '.
    	(($tag instanceof Tag) ? '
    		vsrchhtag = :tag
    	' : '
    		vsrchhtag.slug = :tag
    	') : '')

    	.'
        LEFT JOIN v.genre vg
        WHERE v.active = true
    	'.
    	($taggedentity ? (' AND vhh.' . (($type == 'user') ? 'target' : $type) . ' = :taggedentity  ') : '')
    	.'
    	AND
    	(
    		(v.privacy = :everyone)
    		OR
	    	(v.privacy = :friendsonly AND (:user IS NOT NULL) AND (
	    		(:user = v.author) OR
	    		((SELECT COUNT(f.id) FROM \Dodici\Fansworld\WebBundle\Entity\Friendship f WHERE (f.author = v.author AND f.target = :user) OR (f.target = v.author AND f.author = :user) AND f.active=true) >= 1)
	    	))
    	)
    	AND
    	(
    		(:category IS NULL OR
    			(
    				(:category = false AND v.videocategory IS NULL)
    				OR
    				(:category <> false AND v.videocategory = :category)
    			)
    		)
    	)
    	AND
    	(
    		(:highlighted IS NULL OR
    			(v.highlight = :highlighted)
    		)
    	)
    	AND
    	( :author IS NULL OR (v.author = :author) )
    	AND
    	( :datefrom IS NULL OR (v.createdAt >= :datefrom) )
    	AND
    	( :dateto IS NULL OR (v.createdAt <= :dateto) )
        AND
        (
            (:genre IS NULL OR
                (
                    (:genre <> false AND
                        (vg = :genre OR vg.parent = :genre)
                    )
                )
            )
        )
    	'.
    	($excludeids ? '
    	AND (v.id NOT IN (:excludeids))
    	' : '')
    	.'

    	'.

    	($related ? '
        AND
    	(
    		v.id IN
        	(
        	SELECT vxrel.id FROM \Dodici\Fansworld\WebBundle\Entity\Video vxrel
        	LEFT JOIN vxrel.hastags vhtag
    			WITH (vhtag.tag IN (SELECT bshtag.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTag hsbtag JOIN hsbtag.tag bshtag WHERE hsbtag.video = :related))
    		LEFT JOIN vxrel.hasteams vhteam
    			WITH (vhteam.team IN (SELECT bshteam.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTeam hsbteam JOIN hsbteam.team bshteam WHERE hsbteam.video = :related))
    		LEFT JOIN vxrel.hasidols vhidol
    			WITH (vhidol.idol IN (SELECT bshidol.id FROM \Dodici\Fansworld\WebBundle\Entity\HasIdol hsbidol JOIN hsbidol.idol bshidol WHERE hsbidol.video = :related))
    		GROUP BY vxrel.id
    		HAVING (COUNT(vhtag) + COUNT(vhteam) + COUNT(vhidol)) > 0
    	    )
		)

        ' : '')

        .

        ($recommended ? '
        AND
        (
            v.id IN
            (
            SELECT vxrec.id FROM \Dodici\Fansworld\WebBundle\Entity\Video vxrec
        	LEFT JOIN vxrec.hasteams vhrecteam
                WITH (vhrecteam.team IN (SELECT recishteam.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship rectship JOIN rectship.team recishteam WHERE rectship.author = :user))
            LEFT JOIN vxrec.hasidols vhrecidol
                WITH (vhrecidol.idol IN (SELECT recishidol.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship reciship JOIN reciship.idol recishidol WHERE reciship.author = :user))
            GROUP BY vxrec.id
            HAVING (COUNT(vhrecteam) + COUNT(vhrecidol)) > 0
            )
        )
        ' : '');


        if ($terms) {

    	    foreach ($terms as $k => $t) {
            	$dql .= '
            	AND
            	(
            		(v.title LIKE :term'.$k.')
            		OR
            		(v.content LIKE :term'.$k.')
            		OR
            		(v.id IN (SELECT vhtvtx'.$k.'.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTag vhttx'.$k.' INNER JOIN vhttx'.$k.'.video vhtvtx'.$k.' INNER JOIN vhttx'.$k.'.tag vhtttx'.$k.' WITH vhtttx'.$k.'.title LIKE :term'.$k.'))
            		OR
            		(v.id IN (SELECT vhtvmx'.$k.'.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTeam vhtmx'.$k.' INNER JOIN vhtmx'.$k.'.video vhtvmx'.$k.' INNER JOIN vhtmx'.$k.'.team vhttmx'.$k.' WITH vhttmx'.$k.'.title LIKE :term'.$k.'))
            		OR
            		(v.id IN (SELECT vhtvix'.$k.'.id FROM \Dodici\Fansworld\WebBundle\Entity\HasIdol vhtix'.$k.' INNER JOIN vhtix'.$k.'.video vhtvix'.$k.' INNER JOIN vhtix'.$k.'.idol vhttix'.$k.' WITH (vhttix'.$k.'.firstname LIKE :term'.$k.' OR vhttix'.$k.'.lastname LIKE :term'.$k.')))
            	)
            	';
    	    }

    	}

        $query = $this->_em->createQuery($dql)
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('datefrom', $datefrom)
                ->setParameter('dateto', $dateto)
                ->setParameter('highlighted', $highlighted)
                ->setParameter('author', ($author instanceof User) ? $author->getId() : null)
                ->setParameter('genre', ($genre instanceof Genre) ? $genre->getId() : $genre);

        if ($terms) foreach ($terms as $k => $t) $query = $query->setParameter('term'.$k, '%' . $t . '%');

        if ($taggedentity)
            $query = $query->setParameter('taggedentity', $taggedentity->getId());

        if ($related)
            $query = $query->setParameter('related', $related->getId());

        if ($excludeids)
            $query = $query->setParameter('excludeids', $excludeids);

        if ($tag)
            $query = $query->setParameter('tag', ($tag instanceof Tag) ? $tag->getId() : $tag);

        return $query->getSingleScalarResult();
    }

    /**
     * Get Flumotion videos pending process
     * @param int|null $limit
     */
    public function pendingProcessing($limit = null)
    {
        $query = $this->_em->createQuery('
    	SELECT v, va
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.author va
    	WHERE
    	v.processed = false
    	AND
    	v.stream IS NOT NULL
    	ORDER BY v.createdAt ASC
    	');

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);

        return $query->getResult();
    }

    /**
     * Get highlight videos (idol, team)
     * @param Idol|Team $entity
     * @param int|null $limit
     */
    public function highlights($entity, $limit = null)
    {
        $type = $this->getType($entity);

        $query = $this->_em->createQuery('
    	SELECT v, vi, vhh
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	JOIN v.image vi
    	INNER JOIN v.has' . $type . 's vhh
    	WHERE
    	v.active = true
    	AND
    	vhh.' . (($type == 'user') ? 'target' : $type) . ' = :entid
    	ORDER BY v.highlight DESC, v.createdAt DESC
    	')
                ->setParameter('entid', $entity->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);

        return $query->getResult();
    }

    /**
     * Get a day's videos
     * @param DateTime $date
     * @param int|null $limit
     * @param int|null $offset
     */
    public function dateFromVideos($date, $limit=null, $offset=null)
    {
        $query = $this->_em->createQuery('
            SELECT v
            FROM \Dodici\Fansworld\WebBundle\Entity\Video v
            WHERE v.createdAt >= :date_from AND v.createdAt <= :date_to
            ORDER BY v.visitCount DESC
            ')

        ->setParameter('date_from', $date->format("Y-m-d 00:00:00") )
        ->setParameter('date_to', $date->format("Y-m-d 23:59:59") );

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        return $query->getResult();
    }

    /**
     * Returns videos related to $video, privacy filtered by $viewer if provided
     * @param Video $video
     * @param User|null $viewer
     * @param int|null $limit
     * @param int|null $offset
     */
    public function related(Video $video, User $viewer=null, $limit=null, $offset=null)
    {
        return $this->search(null, $viewer, $limit, $offset, null, null, null, null, null, 'default', null, $video, $video);
    }

    /**
     * Get more videos authored by $author, excluding $video if provided
     * $author=false : site videos
     * @param User|false $author
     * @param Video|null $video
     * @param User|null $viewer
     * @param int|null $limit
     * @param int|null $offset
     */
    public function moreFromUser($author, Video $video=null, User $viewer=null, $limit=null, $offset=null)
    {
        return $this->search(null, $viewer, $limit, $offset, null, null, $author, null, null, 'default', null, $video);
    }

    /**
     * Recommended videos, for a user or not, excluding a video or not
     * @param User|null $viewer
     * @param Video|null $video
     * @param int|null $limit
     * @param int|null $offset
     */
    public function recommended(User $viewer=null, Video $video=null, $limit=null, $offset=null, $genre=null)
    {
        return $this->search(null, $viewer, $limit, $offset, null, true, null, null, null, 'default', null, $video, null, null, null, null, $genre);
    }

    /**
     * Search videos by tag
     *
     * @param string $text - term to search for
     * @param User|null $user - current logged in user, or null
     * @param int|null $limit
     * @param int|null $offset
     * @param string|Tag|null $tag - Tag slug, or entity, to search by
     */
    public function searchByTag($text=null, $user=null, $limit=null, $offset=null, $tag=null)
    {
        return $this->search(
            $text, $user, $limit, $offset, $category = null, null, null, null,
            null, 'default', null, null, null, null, 'DESC', $tag
        );
    }

	/**
     * Count videos by tag
     *
     * @param string $text - term to search for
     * @param User|null $user - current logged in user, or null
     * @param string|Tag|null $tag - Tag slug, or entity, to search by
     */
    public function countByTag($text=null, $user=null, $tag=null)
    {
        return $this->countSearch(
            $text, $user, null, null, null, null, null, null, null, null, null, $tag
        );
    }

    /**
     * Return videos that have been tagged with a team that the user is a fan of
     * @param User $user
     * @param int|null $limit
     */
    public function commonTeams(User $user, $limit=null)
    {
        $query = $this->_em->createQuery('
    	SELECT v, vi, va, COUNT(vhfavteam) as favcnt
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.image vi
    	LEFT JOIN v.author va
    	JOIN v.hasteams vhrecteam
            WITH (vhrecteam.team IN (SELECT recishteam.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship rectship JOIN rectship.team recishteam WHERE rectship.author = :user))
        LEFT JOIN v.hasteams vhfavteam
        	WITH (vhfavteam.team IN (SELECT favishteam.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship favtship JOIN favtship.team favishteam WHERE favtship.author = :user AND favtship.favorite = true))
    	WHERE
    	v.active = true
    	GROUP BY v
    	ORDER BY favcnt DESC, v.weight DESC
    	')
        ->setParameter('user', $user->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);

        $videos = array();
        $res = $query->getResult();
        foreach ($res as $r) $videos[] = $r[0];
        return $videos;
    }

    /**
     * Return videos that have been tagged with an idol that the user is a fan of
     * @param User $user
     * @param int|null $limit
     */
    public function commonIdols(User $user, $limit=null)
    {
        $query = $this->_em->createQuery('
    	SELECT v, vi, va
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.image vi
    	LEFT JOIN v.author va
    	JOIN v.hasidols vhrecidol
            WITH (vhrecidol.idol IN (SELECT recishidol.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship reciship JOIN reciship.idol recishidol WHERE reciship.author = :user))
    	WHERE
    	v.active = true
    	GROUP BY v
    	ORDER BY v.weight DESC
    	')
        ->setParameter('user', $user->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);

        return $query->getResult();
    }

	/**
     * Return highlight videos belonging to a category that the user is subscribed to
     * @param User $user
     * @param int|null $limit
     */
    public function commonCategories(User $user, $limit=null)
    {
        $query = $this->_em->createQuery('
    	SELECT v, vi, va
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.image vi
    	LEFT JOIN v.author va
    	JOIN v.videocategory vc
    	JOIN vc.videocategorysubscriptions vcs
            WITH (vcs.author = :user)
    	WHERE
    	v.active = true
    	AND v.highlight = true
    	GROUP BY v
    	ORDER BY v.createdAt DESC
    	')
        ->setParameter('user', $user->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);

        return $query->getResult();
    }

	/**
     * Return videos that have been tagged with at least one team or idol
     * @param int|null $limit
     * @param int|null $offset
     */
    public function areTagged($limit=null, $offset=null)
    {
        $query = $this->_em->createQuery('
    	SELECT v, COUNT(ht) as cntteams, COUNT(hi) as cntidols
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.hasteams ht
    	LEFT JOIN v.hasidols hi
    	WHERE
    	v.active = true
    	GROUP BY v
    	HAVING (cntteams > 0 OR cntidols > 0)
    	ORDER BY v.weight DESC
    	')
        ;

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        $res = $query->getResult();
        $items = array();
        foreach ($res as $r) $items[] = $r[0];
        return $items;
    }

    /**
     * Get videos related to Genre/Category
     * @param User|null $user
     * @param Genre: null | Id of genre (Int) or Entity type Genre $genre
     * @param VideoCategory: null|$category
     * @param boolean|null $highlighted
     * @param 'default'(weight)|'views'|'likes'|null $sortcriteria
     * @param array<Video|int>|Video|int|null $excludes
     * @param int|null $limit
     * @param int|null $offset
     */
    public function searchHome($user=null, $genre=null, $category=null, $recommended = null, $highlighted=null, $sortcriteria=null, $excludes=null, $limit=null, $offset=null)
    {
        return $this->search(null, $user, $limit, $offset, $category, $highlighted, null, null ,null, $sortcriteria, null, $excludes, null, $recommended, null, null, $genre);
    }

    /**
     *
     */
    public function getVideosTaggedWith($entity, $limit = null, $offset = null)
    {
        return $this->search(null, null, $limit, $offset, null, null, null, null, null, null, $entity, null, null, null, 'desc');
    }
}