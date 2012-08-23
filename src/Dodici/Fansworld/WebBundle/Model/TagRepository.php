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
	
	/**
	 * Matches against a string for user/team/idol entities, for autocomplete, etc
	 * entities of which the user is a fan
	 * 
     * @param string $match
     * @param User|null $user
	 * @param int|null $limit
	 */
    public function matchEntities($match, User $user, $limit=null)
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
    	        $likes[] = $type.'.'.$lf . ' LIKE :textlike';
    	    }
            
    	    $dql = '
        	SELECT '.$type.', img
        	FROM '.$classtypes[$type].' '.$type.'
        	LEFT JOIN '.$type.'.image img
        	'.$joins[$type].'
        	GROUP BY '.$type.'
        	HAVING
        	('.join(' AND ', $likes).')
        	'.(($type=='user') ? '
        		AND (COUNT(uffr) > 0 OR COUNT(uffn) > 0)
        	' : '').'
        	';
    	        	    
            $query = $this->_em->createQuery($dql)
        		->setParameter('textlike', '%'.$match.'%')
        		->setParameter('user', $user->getId());
        		
        	if ($limit !== null)
        	    $query = $query->setMaxResults($limit);
            
        	$results[$type] = $query->getResult();
        }
    	
    	return $results;
    	
        /*
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('type', 'type');
        $rsm->addRootEntityFromClassMetadata('Application\\Sonata\\MediaBundle\\Entity\\Media', 'img');
        
        $columns = $this->_em->getClassMetadata('Application\\Sonata\\MediaBundle\\Entity\\Media')->getColumnNames();
        
        var_dump($columns);
        
        $columns = array('id', 'name', 'description', 'enabled', 'width', 'height', 'length', 'context', 'provider_status');
        
        $columnmaps = array();
        foreach ($columns as $c) {
            $rsm->addFieldResult('img', 'img_'.$c, $c);
            $columnmaps[] = 'img.'.$c.' as img_'.$c;
        }
        
        
        
        $joins = array(
            'user' => 
                'RIGHT JOIN friendship ON
                friendship.active = true AND 
                ((friendship.author_id = :userid AND
                friendship.target_id = user.id)
                OR
                (friendship.target_id = :userid AND
                friendship.author_id = user.id))',
            'idol' => 
            	'RIGHT JOIN idolship ON (idolship.idol_id = idol.id AND idolship.author_id = :userid)',
        	'team' => 
        	    'RIGHT JOIN teamship ON (teamship.team_id = team.id AND teamship.author_id = :userid)'
        );
        
        $sqls = array();
        foreach (array('user', 'idol', 'team') as $type) {
            $sql = '
                SELECT
                '.$type.'.id as id,
                '.
                ((in_array($type, array('idol', 'user'))) ?
                ('CONCAT('.$type.'.firstname, \' \', '.$type.'.lastname) AS title,') :
                ($type.'.title as title,'))
                .'
                '.(($type == 'user') ? 'user.username' : ($type.'.slug')).' as slug,
                \''.$type.'\' as type,
                img.*
                FROM
                '.(($type == 'user') ? 'fos_user_user as user' : $type).'
                INNER JOIN media__media img ON '.$type.'.image_id = img.id
                '.$joins[$type].'
                
                ';

            
            
            $sql .= ' GROUP BY '.$type.'.id HAVING title LIKE :titlelike';
            
            $sqls[] = $sql;
        }
        
        $query = $this->_em->createNativeQuery(
            join(' UNION ', $sqls) .'
            
            '.
            (($limit !== null) ? ' LIMIT :limit ' : '')
            , $rsm
        )
        ->setParameter('titlelike', '%'.$match.'%', Type::STRING)
        ->setParameter('userid', $user->getId(), Type::BIGINT);
          
        if ($limit !== null)
            $query = $query->setParameter('limit', (int)$limit, Type::INTEGER);
            
        return $query->getResult();*/
	} 
}