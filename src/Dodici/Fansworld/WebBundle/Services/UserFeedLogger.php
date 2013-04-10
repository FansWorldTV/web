<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\HasTag;
use Dodici\Fansworld\WebBundle\Entity\HasIdol;
use Dodici\Fansworld\WebBundle\Entity\HasTeam;
use Dodici\Fansworld\WebBundle\Entity\HasUser;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Dodici\Fansworld\WebBundle\Entity\Activity;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * User feed logger service
 */
class UserFeedLogger
{

    protected $security_context;
    protected $em;
    protected $user;
    protected $appfacebook;

    function __construct(SecurityContext $security_context, EntityManager $em, $appfacebook)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->appfacebook = $appfacebook;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function log($type, $entities=array(), $author=null, $flush=true)
    {
        if ($author === null) $author = $this->user;
        if ($author === false) $author = null;
        
        $validtypes = array_keys(Activity::getTypeList());
        if (!in_array($type, $validtypes)) throw new \Exception('Invalid activity type');
        
        if ($entities && !is_array($entities)) $entities = array($entities);
        
        $activity = new Activity();
        $activity->setAuthor($author);
        $activity->setType($type);
        
        foreach ($entities as $entity) {
            if ($entity) {
                $entitytype = $this->getType($entity);
                
                if ($type == Activity::TYPE_BECAME_FAN && $author) {
                    $this->appfacebook->fan($entity, $author);
                }
                
                if ($type == Activity::TYPE_LIKED && $author) {
                    $this->appfacebook->like($entity, $author);
                }
                
                switch ($entitytype) {
                    case 'user':
                        $has = new HasUser();
                        $has->setAuthor($author);
                        $has->setTarget($entity);
                        $activity->addHasUser($has);
                        break;
                    case 'team':
                    case 'idol':
                    case 'tag':
                        $hasclass = 'Dodici\\Fansworld\\WebBundle\\Entity\\Has'.ucfirst($entitytype);
                        $has = new $hasclass();
                        if ($author) $has->setAuthor($author);
                        $has->{'set'.ucfirst($entitytype)}($entity);
                        $activity->{'addHas'.ucfirst($entitytype)}($has);
                        break;
                    default:
                        $activity->{'set'.ucfirst($entitytype)}($entity);
                        break;
                }
            }
        }
        
        $this->em->persist($activity);
        if ($flush) $this->em->flush();
    }
    
    private function getType($entity)
    {
        $name = $this->em->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }
}