<?php

namespace Dodici\Fansworld\WebBundle\Model;

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

}