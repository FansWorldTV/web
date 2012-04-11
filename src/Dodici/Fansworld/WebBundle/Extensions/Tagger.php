<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Dodici\Fansworld\WebBundle\Entity\Tag;
use Dodici\Fansworld\WebBundle\Entity\HasTag;
use Dodici\Fansworld\WebBundle\Entity\HasUser;
use Gedmo\Sluggable\Util\Urlizer as GedmoUrlizer;

class Tagger
{
	protected $request;
	protected $em;

    function __construct(EntityManager $em)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
    }

    /**
     * Add a tag to an entity, create new tag if it doesn't already exist
     * @param User $user
     * @param $entity
     * @param array|string $tag
     */
    public function tag(User $user, $entity, $tag)
    {
    	if (!is_array($tag)) {
    		$tag = array($tag);
    	}
    	
    	$tagrepo = $this->em->getRepository('DodiciFansworldWebBundle:Tag');
    	$hasrepo = $this->em->getRepository('DodiciFansworldWebBundle:HasTag');
    	$hasurepo = $this->em->getRepository('DodiciFansworldWebBundle:HasUser');
    	$exp = explode('\\', get_class($entity));
    	$classname = end($exp);
    	
    	foreach ($tag as $t) {
    		if ($t instanceof User) {
    			$exists = $hasurepo->findOneBy(array('target' => $t->getId(), 'author' => $user->getId(), strtolower($classname) => $entity->getId()));
	    		
	    		if (!$exists) {
		    		$hasuser = new HasUser();
		    		$hasuser->setAuthor($user);
		    		$hasuser->setTarget($t);
		    		$methodname = 'set'.$classname;
		    		$hasuser->$methodname($entity);
		    		
		    		$entity->addHasUser($hasuser);
		    		$this->em->persist($entity);
	    		}
    		} else {
    			$slugt = GedmoUrlizer::urlize($t);
    			if ($slugt) {
		    		$tagent = $tagrepo->findOneBy(array('slug' => $slugt));
		    		
		    		if (!$tagent) {
		    			$tagent = new Tag();
		    			$tagent->setTitle($t);
		    			$this->em->persist($tagent);
		    		}
		    		
		    		$exists = $hasrepo->findOneBy(array('tag' => $tagent->getId(), 'author' => $user->getId(), strtolower($classname) => $entity->getId()));
		    		
		    		if (!$exists) {
			    		$hastag = new HasTag();
			    		$hastag->setAuthor($user);
			    		$hastag->setTag($tagent);
			    		$methodname = 'set'.$classname;
			    		$hastag->$methodname($entity);
			    		
			    		$entity->addHasTag($hastag);
			    		$this->em->persist($entity);
		    		}
    			}
    		}
    	}
    	
    	$this->em->flush();
    }
    
}