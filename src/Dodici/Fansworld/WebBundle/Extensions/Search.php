<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Entity\Idol;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

/**
 * Search service.
 * Classes involved (type) must implement the Searchable interface
 * Their repositories must have a search($text, $user=null, $limit=null, $offset=null) compatible method
 */
class Search
{
    const TYPE_TEAM = 1;
    const TYPE_VIDEO = 2;
    const TYPE_PHOTO = 4;
    const TYPE_EVENT = 8;
    const TYPE_USER = 16;
    const TYPE_IDOL = 32;
    const TYPE_FORUM = 64;
    
	public static function getTypes()
    {
    	return array(
    		self::TYPE_TEAM => 'Dodici\\Fansworld\\WebBundle\\Entity\\Team',
    		self::TYPE_VIDEO => 'Dodici\\Fansworld\\WebBundle\\Entity\\Video',
    		self::TYPE_PHOTO => 'Dodici\\Fansworld\\WebBundle\\Entity\\Photo',
    		self::TYPE_EVENT => 'Dodici\\Fansworld\\WebBundle\\Entity\\Event',
    		self::TYPE_USER => 'Application\\Sonata\\UserBundle\\Entity\\User',
    		self::TYPE_IDOL => 'Dodici\\Fansworld\\WebBundle\\Entity\\Idol',
    		self::TYPE_FORUM => 'Dodici\\Fansworld\\WebBundle\\Entity\\ForumThread',
    	);
    }
	
	protected $security_context;
    protected $request;
    protected $em;
    protected $router;
    protected $user;

    function __construct(SecurityContext $security_context, EntityManager $em, Router $router)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->router = $router;
        $this->user = ($security_context->getToken()->getUser() instanceof User) ? $security_context->getToken()->getUser() : null;
    }
    
    private function getRepositoryByType($type)
    {
    	$types = self::getTypes();
    	if (!in_array($type, array_keys($types))) throw new \Exception('Unsupported search type');
    	return $this->em->getRepository($types[$type]);
    }
    
	private function getClass($entity)
    {
        $exp = explode('\\', get_class($entity));
        $classname = strtolower(end($exp));
        if (strpos($classname, 'proxy') !== false) {
            $classname = str_replace(array('dodicifansworldwebbundleentity', 'proxy'), array('', ''), $classname);
        }
        return $classname;
    }
    
    public function search($text, $type, $limit=null, $offset=null)
    {
    	$repo = $this->getRepositoryByType($type);
    	return $repo->search($text, $this->user, $limit, $offset);
    }
    
    public function count($text, $type)
    {
    	$repo = $this->getRepositoryByType($type);
    	return $repo->countSearch($text, $this->user);
    }
    
    public function getUrl($entity, $absolute=false)
    {
    	$class = $this->getClass($entity);
    	switch ($class) {
    		case 'user' :
    			return $this->router->generate('user_wall', array('username' => $entity->getUsername()), $absolute);
    			break;
    		case 'idol' :
    			return $this->router->generate('idol_wall', array('slug' => $entity->getSlug()), $absolute);
    			break;
    		default:
    			return $this->router->generate($class.'_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), $absolute);
    			break;
    	}
    }
}