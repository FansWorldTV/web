<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Model\SearchableInterface;

use Dodici\Fansworld\WebBundle\Entity\Idol;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Dodici\Fansworld\WebBundle\Entity\SearchHistory;

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

    public static function humanTypes()
    {
        return array(
            'team' => self::TYPE_TEAM,
            'video' => self::TYPE_VIDEO,
            'photo' => self::TYPE_PHOTO,
            'event' => self::TYPE_EVENT,
            'user' => self::TYPE_USER,
            'idol' => self::TYPE_IDOL,
            'forum' => self::TYPE_FORUM,
        );
    }

    protected $security_context;
    protected $request;
    protected $em;
    protected $router;
    protected $user;
    protected $appstate;

    function __construct(SecurityContext $security_context, EntityManager $em, Router $router, $appstate)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->router = $router;
        $this->user = ($security_context->getToken() && ($security_context->getToken()->getUser() instanceof User)) ? $security_context->getToken()->getUser() : null;
        $this->appstate = $appstate;
    }



    public function search($text, $type, $user = null, $limit = null, $offset = null)
    {
        $repo = $this->getRepositoryByType($type);
        return $repo->search($text, $user, $limit, $offset);
    }

    public function count($text, $type, $user = null)
    {
        $repo = $this->getRepositoryByType($type);
        return $repo->countSearch($text, $user);
    }

    /**
     * Get url for a searchable entity
     * @param SearchableInterface $entity
     * @param boolean $absolute
     */
    public function getUrl(SearchableInterface $entity, $absolute = false)
    {
        $class = $this->appstate->getType($entity);
        switch ($class) {
            case 'user' :
                return $this->router->generate('user_wall', array('username' => $entity->getUsername()), $absolute);
                break;
            case 'idol' :
                return $this->router->generate('idol_wall', array('slug' => $entity->getSlug()), $absolute);
                break;
            default:
                return $this->router->generate($class . '_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), $absolute);
                break;
        }
    }

    /**
     * Get top searched terms, for autocomplete, etc.
     * @param string|null $match - String to match terms against, term%
	 * @param User|null $user - filter by user
	 * @param string|null $ip - filter by ip
	 * @param (int)Services\Search::TYPE_*|null $type - filter by type of search
	 * @param int|null $limit
	 * @param int|null $offset
     */
    public function topTerms($match=null, $user=null, $ip=null, $type=null, $limit=null, $offset=null)
    {
        return $this->em->getRepository('DodiciFansworldWebBundle:SearchHistory')
            ->topTerms($match, $user, $ip, $type, $limit, $offset);
    }
    
	/**
     *
     * @return boolean
     */
    public function log($query, $user, $ip, $device) {
        $log = new SearchHistory();
        $log->setTerm($query);
        $log->setAuthor($user);
        $log->setIp($ip);
        $log->setDevice($device);
        $this->em->persist($log);
        $this->em->flush();
        return true;
    }

    private function getRepositoryByType($type)
    {
        $types = self::getTypes();
        $humantypes = self::humanTypes();
        if (in_array($type, array_keys($humantypes))) $type = $humantypes[$type];
        if (!in_array($type, array_keys($types)))
            throw new \Exception('Unsupported search type');
        return $this->em->getRepository($types[$type]);
    }

    


}