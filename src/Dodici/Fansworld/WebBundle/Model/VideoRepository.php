<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Video;

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
     * Search videos by text/tag, visible to the user
     * 
     * @param string|Tag|null $searchterm
     * @param User|null $user
     * @param int|null $limit
     * @param int|null $offset
     * @param VideoCategory|null $category
     * @param boolean|null $highlighted
     * @param User|null $author
     * @param DateTime|null $datefrom
     * @param DateTime|null $dateto
     * @param 'default'|'views'|'likes' $sortcriteria
     * @param User|Idol|Team|null $taggedentity
     * @param array<Video|int>|Video|int|null $excludes
     * @param Video|null $related
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
        $sortcriteria = 'default',
        $taggedentity = null,
        $excludes = null,
        $related = null
    )
    {
        if(!$sortcriteria)
        {
            $sortcriteria = 'default';
        }
        
        $sortcriterias = array(
            'default' => 'v.weight DESC',
            'views' => 'v.viewCount DESC',
            'likes' => 'v.likeCount DESC',
            'date' => 'v.createdAt DESC'
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

        $query = $this->_em->createQuery('
    	SELECT v, va '.($related ? ', (COUNT(vhtag) + COUNT(vhteam) + COUNT(vhidol)) common' : '').'
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	LEFT JOIN v.author va
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
        
    	.'
    	WHERE v.active = true
    	AND
    	'.
    	($taggedentity ? ' vhh.' . (($type == 'user') ? 'target' : $type) . ' = :taggedentity AND ' : '')
    	.'
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
    	( :author IS NULL OR (v.author = :author) )
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
    	.'
    	
    	ORDER BY 
    	
    	' . ($related ? 'common DESC, ' : '') . '
    	
    	' . $sortcriterias[$sortcriteria] . '
    	
    	')
                ->setParameter('searchterm', $searchterm)
                ->setParameter('searchlike', '%' . $searchterm . '%')
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('datefrom', $datefrom)
                ->setParameter('dateto', $dateto)
                ->setParameter('highlighted', $highlighted)
                ->setParameter('author', ($author instanceof User) ? $author->getId() : null);
                
        if ($taggedentity)
            $query = $query->setParameter('taggedentity', $taggedentity->getId());
            
        if ($related)
            $query = $query->setParameter('related', $related->getId());
            
        if ($excludeids)
            $query = $query->setParameter('excludeids', $excludeids);

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if ($offset !== null)
            $query = $query->setFirstResult((int) $offset);

        $res = $query->getResult();
        
        if ($related) {
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
     */
    public function countSearch(
        $searchterm = null, 
        $user = null, 
        $category = null, 
        $highlighted = null, 
        $author = null,
        $datefrom = null,
        $dateto = null,
        $taggedentity = null
    )
    {

        if ($taggedentity) {
            $type = $this->getType($taggedentity);
        }
        
        $query = $this->_em->createQuery('
    	SELECT COUNT(v.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Video v
    	'.
    	($taggedentity ? ' INNER JOIN v.has' . $type . 's vhh ' : '')
    	.'
    	WHERE v.active = true
    	AND
    	'.
    	($taggedentity ? ' vhh.' . (($type == 'user') ? 'target' : $type) . ' = :taggedentity AND ' : '')
    	.'
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
    	')
                ->setParameter('searchterm', $searchterm)
                ->setParameter('searchlike', '%' . $searchterm . '%')
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setParameter('friendsonly', Privacy::FRIENDS_ONLY)
                ->setParameter('user', ($user instanceof User) ? $user->getId() : null)
                ->setParameter('category', ($category instanceof VideoCategory) ? $category->getId() : $category)
                ->setParameter('datefrom', $datefrom)
                ->setParameter('dateto', $dateto)
                ->setParameter('highlighted', $highlighted)
                ->setParameter('author', ($author instanceof User) ? $author->getId() : null);
                
        if ($taggedentity)
            $query = $query->setParameter('taggedentity', $taggedentity->getId());

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
     * @param User $author
     * @param Video|null $video
     * @param User|null $viewer
     * @param int|null $limit
     * @param int|null $offset
     */
    public function moreFromUser(User $author, Video $video=null, User $viewer=null, $limit=null, $offset=null)
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
    public function recommended(User $viewer=null, Video $video=null, $limit=null, $offset=null)
    {
        return $this->search(null, $viewer, $limit, $offset, null, true, null, null, null, 'default', null, $video);
    }
}