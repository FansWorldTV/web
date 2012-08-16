<?php

namespace Dodici\Fansworld\WebBundle\Model;

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
	public function matching($text=null, $limit=null, $offset=null)
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
    		->setParameter('textlike', '%'.$text.'%');
    		
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
	 * @param int|null $limit
	 * @param int|null $offset
	 */
    public function usedInVideos($filtertype, $videocategory=null, $limit=null, $offset=null)
	{
        $filtertypes = array('popular', 'latest');
        
        if (!in_array($filtertype, $filtertypes)) throw new \InvalidArgumentException('Invalid filter type');
        
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
                '.$type.'.id as id,
                '.
                (($type == 'idol') ?
                ('CONCAT(idol.firstname, \' \', idol.lastname) AS title,') :
                ($type.'.title as title,'))
                .'
                '.$type.'.slug as slug,
                COUNT(has'.$type.'.id) AS usecount,
                AVG(video.weight) AS avgweight,
                MAX(has'.$type.'.created_at) as latest,
                \''.$type.'\' as type
                FROM
                has'.$type.'
                INNER JOIN '.$type.' ON has'.$type.'.'.$type.'_id = '.$type.'.id
                INNER JOIN video ON has'.$type.'.video_id = video.id
                '.
                (($videocategory) ? 
                'WHERE
                (:videocategory IS NULL OR (video.videocategory_id = :videocategory))' : '')
                .'
                GROUP BY '.$type.'.id
                ';
        }
        
        $order = null;
        if ($filtertype == 'popular') $order = 'avgweight DESC';
        if ($filtertype == 'latest') $order = 'latest DESC';
                
        $query = $this->_em->createNativeQuery(
            join(' UNION ', $sqls) .'
            ORDER BY 
            '.$order.'
            '.
            (($limit !== null) ? ' LIMIT :limit ' : '').
            (($offset !== null) ? ' OFFSET :offset ' : '')
            , $rsm
        );
          
        if ($videocategory) {
            $query = $query->setParameter(
               	'videocategory', 
                ($videocategory instanceof VideoCategory) ? $videocategory->getId() : $videocategory, 
                Type::BIGINT
            );
        }

        if ($limit !== null)
            $query = $query->setParameter('limit', (int)$limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int)$offset, Type::INTEGER);
        return $query->getResult();
	} 
}