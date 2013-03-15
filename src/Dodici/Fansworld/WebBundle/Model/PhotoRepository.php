<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Tag;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\ORM\EntityRepository;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Album;

/**
 * PhotoRepository
 */
class PhotoRepository extends CountBaseRepository
{

    /**
     * Get the next active Photo by id
     * @param int $id
     */
    public function getNextActive($id, User $author, Album $album)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
                ->add('select', 'p')
                ->add('from', $this->_entityName . ' p')
                ->add('where', 'p.id > ?1 AND p.active=1 AND p.author = ?2 AND p.album = ?3')
                ->setMaxResults(1)
                ->setParameter(1, $id)
                ->setParameter(2, $author)
                ->setParameter(3, $album);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Get the previous active Photo by id
     * @param int $id
     */
    public function getPrevActive($id, User $author, Album $album)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
                ->add('select', 'p')
                ->add('from', $this->_entityName . ' p')
                ->add('where', 'p.id < ?1 AND p.active=1 AND p.author = ?2 AND p.album = ?3')
                ->add('orderBy', 'p.id DESC')
                ->setMaxResults(1)
                ->setParameter(1, $id)
                ->setParameter(2, $author)
                ->setParameter(3, $album);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Whether or not a Media has an associated Photo that uses it
     * @param Media $image
     */
    public function byImage(Media $image)
    {
        $query = $this->_em->createQuery('
    	SELECT COUNT(p.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	WHERE
    	p.image = :image
    	AND p.privacy <> :everyone
    	')
                ->setParameter('image', $image->getId())
                ->setParameter('everyone', Privacy::EVERYONE)
                ->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * Get photos by entity (idol, team, user)
     * @param Idol|Team $entity
     */
    public function searchByEntity($entity, $limit = null, $offset = null)
    {
        $type = $this->getType($entity);

        $query = $this->_em->createQuery('
    	SELECT p
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	INNER JOIN p.has' . $type . 's phh
    	WHERE
    	p.active = true
    	AND
    	phh.'.$type.' = :entid
    	ORDER BY p.createdAt DESC
    	')
                ->setParameter('entid', $entity->getId());

        if ($limit !== null)
            $query = $query->setMaxResults((int) $limit);
        if($offset !== null)
            $query = $query->setFirstResult((int) $offset);
        
        return $query->getResult();
    }
    
    /**
     * count photos by entity
     *  @param Idol|Team $entity 
     */
    public function countByEntity($entity, $offset = null)
    {
        $type = $this->getType($entity);

        $query = $this->_em->createQuery('
    	SELECT count(p.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	INNER JOIN p.has' . $type . 's phh
    	WHERE
    	p.active = true
    	AND
    	phh.'.$type.' = :entid
    	ORDER BY p.createdAt DESC
    	')
                ->setParameter('entid', $entity->getId());

        if($offset !== null)
            $query = $query->setFirstResult((int) $offset);
        
        
        return $query->getSingleScalarResult();
    }
    
	/**
     * Search photos
     * 
     * @param string $text - term to search for
     * @param User|null $user - current logged in user, or null
     * @param int|null $limit
     * @param int|null $offset
     * @param string|Tag|null $tag - Tag slug, or entity, to search by
     */
    public function search($text=null, $user=null, $limit=null, $offset=null, $tag=null)
    {
        $terms = array();
        $xp = explode(' ', $text);
        foreach ($xp as $x) if (trim($x)) $terms[] = trim($x);
        
        $dql = '
    	SELECT p
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	LEFT JOIN p.hastags pht
    	LEFT JOIN pht.tag phtag
        WHERE p.active = true
        ';
        
        if ($terms) {
            foreach ($terms as $k => $t) {
                $dql .= '
            	AND
            	(
            		(p.title LIKE :term'.$k.')
                	OR
                	(p.content LIKE :term'.$k.')
                	OR
                	(phtag.title LIKE :term'.$k.')
            	)
            	';
                
            }
        }
        
        $dql .=
    	($tag ? '
    	AND '.
    	(($tag instanceof Tag) ? '
    		phtag = :tag
    	' : '
    		phtag.slug = :tag
    	')
    	.'
    	' : '')
    	.' 
        ORDER BY p.weight DESC
        ';
        
        $query = $this->_em->createQuery($dql);
        
        if ($terms) foreach ($terms as $k => $t) $query = $query->setParameter('term'.$k, '%' . $t . '%');
        
        if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	if ($tag) $query = $query->setParameter('tag', ($tag instanceof Tag) ? $tag->getId() : $tag);
            
        return $query->getResult();
    }

	/**
     * Count search photos
     * 
     * @param string $text - term to search for
     * @param User|null $user - current logged in user, or null
     * @param string|Tag|null $tag - Tag slug, or entity, to search by
     */
    public function countSearch($text=null, $user=null, $tag=null)
    {
        $terms = array();
        $xp = explode(' ', $text);
        foreach ($xp as $x) if (trim($x)) $terms[] = trim($x);
        
        $dql = '
    	SELECT COUNT(e.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo e
    	'.
        ($tag ? 
    	('
    	JOIN e.hastags eht
    	JOIN eht.tag ehtag WITH
    	' .
        (($tag instanceof Tag) ? '
    		ehtag = :tag
    	' : '
    		ehtag.slug = :tag
    	')) : '')
        .'
    	WHERE e.active = true
    	';
        
        if ($terms) {
            foreach ($terms as $k => $t) {
                $dql .= ' AND
                (
                	(e.title LIKE :term'.$k.')
                	OR
                	(e.content LIKE :term'.$k.')
                	OR
                	(
                		e.id IN (
                			SELECT ex'.$k.'.id
            				FROM \Dodici\Fansworld\WebBundle\Entity\Photo ex'.$k.'
            				JOIN ex'.$k.'.hastags ex'.$k.'ht
            				JOIN ex'.$k.'ht.tag ex'.$k.'htag WITH ex'.$k.'htag.title LIKE :term'.$k.'
                		)
                	)
                )
                ';
            }
        }
        
        $query = $this->_em->createQuery($dql);
        if ($terms) foreach ($terms as $k => $t) $query = $query->setParameter('term'.$k, '%' . $t . '%');
    	if ($tag) $query = $query->setParameter('tag', ($tag instanceof Tag) ? $tag->getId() : $tag);
            
        return $query->getSingleScalarResult();
    }
    
	/**
     * Get photos where the user has been tagged
     * 
     * @param User $user - tagged user
     * @param int|null $limit
     * @param int|null $offset
     */
    public function userTagged(User $user=null, $limit=null, $offset=null)
    {
        $dql = '
    	SELECT p
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	JOIN p.hasusers phu WITH phu.target = :user
    	WHERE p.active = true
        
        ORDER BY phu.createdAt DESC
        ';
        
        $query = $this->_em->createQuery($dql);
        $query = $query->setParameter('user', $user->getId());
        if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
        
        return $query->getResult();
    }
    
	/**
     * Get photos liked by the user lately
     * 
     * @param User $user - tagged user
     * @param int|null $limit
     * @param int|null $offset
     */
    public function userLiked(User $user=null, $limit=null, $offset=null)
    {
        $dql = '
    	SELECT p
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	JOIN p.likings pl WITH pl.author = :user
    	WHERE p.active = true
        
        ORDER BY pl.createdAt DESC
        ';
        
        $query = $this->_em->createQuery($dql);
        $query = $query->setParameter('user', $user->getId());
        if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
        
        return $query->getResult();
    }
    
	/**
     * Count photos where the user has been tagged
     * 
     * @param User $user - tagged user
     */
    public function countUserTagged(User $user=null)
    {
        $dql = '
    	SELECT COUNT(p)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	JOIN p.hasusers phu WITH phu.target = :user
    	WHERE p.active = true
        ';
        
        $query = $this->_em->createQuery($dql);
        $query = $query->setParameter('user', $user->getId());
        
        return (int)$query->getSingleScalarResult();
    }
    
	/**
     * Count photos liked by the user lately
     * 
     * @param User $user - tagged user
     */
    public function countUserLiked(User $user=null)
    {
        $dql = '
    	SELECT COUNT(p)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	JOIN p.likings pl WITH pl.author = :user
    	WHERE p.active = true
        ';
        
        $query = $this->_em->createQuery($dql);
        $query = $query->setParameter('user', $user->getId());
        
        return (int)$query->getSingleScalarResult();
    }
    
	/**
     * Return photos that have been tagged with at least one team or idol
     * @param int|null $limit
     * @param int|null $offset
     */
    public function areTagged($limit=null, $offset=null)
    {
        $query = $this->_em->createQuery('
    	SELECT p, COUNT(ht) as cntteams, COUNT(hi) as cntidols
    	FROM \Dodici\Fansworld\WebBundle\Entity\Photo p
    	LEFT JOIN p.hasteams ht
    	LEFT JOIN p.hasidols hi
    	WHERE
    	p.active = true
    	GROUP BY p
    	HAVING (cntteams > 0 OR cntidols > 0)
    	ORDER BY p.weight DESC
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
}