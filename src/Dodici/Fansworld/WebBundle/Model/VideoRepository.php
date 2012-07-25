<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\VideoCategory;
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
     * Search videos by text, visible to the user
     * @param User $user
     * @param string $searchterm
     * @param int $limit
     * @param int $offset
     */
    public function searchText($searchterm = null, $user = null, $limit = null, $offset = null, $category = null, $isfromuser = null, $sortcriteria = 'default')
    {
        $sortcriterias = array(
            'default' => 'v.highlight DESC, v.viewCount DESC',
            'views' => 'v.viewCount DESC',
            'likes' => 'v.likeCount DESC'
        );

        $query = $this->_em->createQuery('
    	SELECT v, va
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.author va
    	WHERE v.active = true
    	AND
    	(:searchterm IS NULL OR (
    		(v.title LIKE :searchlike)
    		OR
    		(v.content LIKE :searchlike)
    		OR
    		(v.id IN (SELECT vhtv.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTag vht INNER JOIN vht.video vhtv INNER JOIN vht.tag vhtt WITH vhtt.title = :searchterm))
    	))
    	AND
    	(
    		(v.privacy = :everyone)
    		OR
	    	(v.privacy = :friendsonly AND (:user IS NOT NULL) AND (
	    		(SELECT COUNT(f.id) FROM \Dodici\Fansworld\WebBundle\Entity\Friendship f WHERE (f.author = v.author AND f.target = :user) OR (f.target = v.author AND f.author = :user) AND f.active=true) >= 1
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
    		(:isfromuser IS NULL OR
    			(:isfromuser = true AND v.author IS NOT NULL)
    			OR
    			(:isfromuser = false AND v.author IS NULL)
    		)
    	)
    	ORDER BY v.createdAtWeek DESC,
    	
    	' . $sortcriterias[$sortcriteria] . '
    	
    	')
                ->setParameter('searchterm', $searchterm)
                ->setParameter('searchlike', '%' . $searchterm . '%')
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('isfromuser', $isfromuser);

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        return $query->getResult();
    }

    /**
     * Search videos by tag, visible to the user
     * @param User $user
     * @param Tag $tag
     * @param int $limit
     * @param int $offset
     */
    public function byTag(Tag $tag, $user = null, $limit = null, $offset = null, $category = null, $isfromuser = null, $sortcriteria = 'default')
    {
        $sortcriterias = array(
            'default' => 'v.highlight DESC, v.viewCount DESC',
            'views' => 'v.viewCount DESC',
            'likes' => 'v.likeCount DESC'
        );

        $query = $this->_em->createQuery('
    	SELECT v, va
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.author va
    	INNER JOIN v.hastags vht
    	INNER JOIN vht.tag vhtag
    	WHERE v.active = true
    	AND
    	(:tag = vhtag)
    	AND
    	(
    		(v.privacy = :everyone)
    		OR
	    	(v.privacy = :friendsonly AND (:user IS NOT NULL) AND (
	    		(SELECT COUNT(f.id) FROM \Dodici\Fansworld\WebBundle\Entity\Friendship f WHERE (f.author = v.author AND f.target = :user) OR (f.target = v.author AND f.author = :user) AND f.active=true) >= 1
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
    		(:isfromuser IS NULL OR
    			(:isfromuser = true AND v.author IS NOT NULL)
    			OR
    			(:isfromuser = false AND v.author IS NULL)
    		)
    	)
    	ORDER BY v.createdAtWeek DESC,
    	' . $sortcriterias[$sortcriteria] . '
    	')
                ->setParameter('tag', $tag->getId())
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('isfromuser', $isfromuser);

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        return $query->getResult();
    }

    /**
     * Count videos by text, visible to the user
     * @param User $user
     * @param string $searchterm
     */
    public function countSearchText($searchterm = null, $user = null, $category = null, $isfromuser = null)
    {

        $query = $this->_em->createQuery('
    	SELECT COUNT(v.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	WHERE v.active = true
    	AND
    	(:searchterm IS NULL OR (
    		(v.title LIKE :searchlike)
    		OR
    		(v.content LIKE :searchlike)
    		OR
    		(v.id IN (SELECT vhtv.id FROM \Dodici\Fansworld\WebBundle\Entity\HasTag vht INNER JOIN vht.video vhtv INNER JOIN vht.tag vhtt WITH vhtt.title = :searchterm))
    	))
    	AND
    	(
    		(v.privacy = :everyone)
    		OR
	    	(v.privacy = :friendsonly AND (:user IS NOT NULL) AND (
	    		(SELECT COUNT(f.id) FROM \Dodici\Fansworld\WebBundle\Entity\Friendship f WHERE (f.author = v.author AND f.target = :user) OR (f.target = v.author AND f.author = :user) AND f.active=true) >= 1
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
    		(:isfromuser IS NULL OR
    			(:isfromuser = true AND v.author IS NOT NULL)
    			OR
    			(:isfromuser = false AND v.author IS NULL)
    		)
    	)
    	')
                ->setParameter('searchterm', $searchterm)
                ->setParameter('searchlike', '%' . $searchterm . '%')
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('isfromuser', $isfromuser);

        return $query->getSingleScalarResult();
    }

    /**
     * Count videos by tag, visible to the user
     * @param User $user
     * @param Tag $tag
     */
    public function countByTag(Tag $tag, $user = null, $category = null, $isfromuser = null)
    {

        $query = $this->_em->createQuery('
    	SELECT COUNT(v.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	INNER JOIN v.hastags vht
    	INNER JOIN vht.tag vhtag
    	WHERE v.active = true
    	AND
    	(:tag = vhtag)
    	AND
    	(
    		(v.privacy = :everyone)
    		OR
	    	(v.privacy = :friendsonly AND (:user IS NOT NULL) AND (
	    		(SELECT COUNT(f.id) FROM \Dodici\Fansworld\WebBundle\Entity\Friendship f WHERE (f.author = v.author AND f.target = :user) OR (f.target = v.author AND f.author = :user) AND f.active=true) >= 1
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
    		(:isfromuser IS NULL OR
    			(:isfromuser = true AND v.author IS NOT NULL)
    			OR
    			(:isfromuser = false AND v.author IS NULL)
    		)
    	)
    	')
                ->setParameter('tag', $tag->getId())
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('isfromuser', $isfromuser);

        return $query->getSingleScalarResult();
    }

    /**
     * Get Flumotion videos pending process
     * @param int $limit
     */
    public function pendingProcessing($limit = null)
    {
        $query = $this->_em->createQuery('
    	SELECT v, va
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	JOIN v.author va
    	WHERE
    	v.processed = false
    	AND
    	v.stream IS NOT NULL
    	AND
    	v.author IS NOT NULL
    	ORDER BY v.createdAt ASC
    	');

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);

        return $query->getResult();
    }

    /**
     * Get highlight videos (idol, team)
     * @param Idol|Team $entity
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
    	vhh.' . $type . ' = :entid
    	ORDER BY v.highlight DESC, v.createdAt DESC
    	')
                ->setParameter('entid', $entity->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);

        return $query->getResult();
    }

    public function dateFromVideos($date, $limit, $offset)
    {
        $query = $this->_em->createQuery('
            SELECT v
            FROM \Dodici\Fansworld\WebBundle\Entity\Video v
            WHERE v.createdAt >= :date_from AND v.createdAt <= :date_to
            ORDER BY v.createdAt DESC
            ')
                
        ->setParameter('date_from', $date->format("Y-m-d 00:00:00") )
        ->setParameter('date_to', $date->format("Y-m-d 23:59:59") );

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);
        
        return $query->getResult();
    } 

}