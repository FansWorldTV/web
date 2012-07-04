<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Privacy;

use Application\Sonata\MediaBundle\Entity\Media;

use Doctrine\ORM\EntityRepository;

/**
 * PhotoRepository
 */
class PhotoRepository extends CountBaseRepository
{
    /**
     * Get the next active Photo by id
     * @param int $id
     */
    public function getNextActive($id)
    {
        $qb = $this->_em->createQueryBuilder();
        
        $qb
        ->add('select', 'p')
        ->add('from', $this->_entityName . ' p')
        ->add('where', 'p.id > ?1 AND p.active=1')
        ->setMaxResults(1)
        ->setParameter(1, $id);
        
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * Get the previous active Photo by id
     * @param int $id
     */
    public function getPrevActive($id)
    {
        $qb = $this->_em->createQueryBuilder();
        
        $qb
        ->add('select', 'p')
        ->add('from', $this->_entityName . ' p')
        ->add('where', 'p.id < ?1 AND p.active=1')
        ->add('orderBy', 'p.id DESC')
        ->setMaxResults(1)
        ->setParameter(1, $id);
        
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
}