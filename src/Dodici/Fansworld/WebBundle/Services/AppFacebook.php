<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class AppFacebook
{

    protected $security_context;
    protected $request;
    protected $em;
    protected $user;
    protected $facebook;
    protected $router;
    protected $translator;
    protected $appmedia;
    protected $scope;
    protected $feedenabled;
    protected $namespace;

    function __construct(SecurityContext $security_context, EntityManager $em, $facebook, $router, $translator, $appmedia, $scope=array(), $feedenabled, $namespace)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        $this->facebook = $facebook;
        $this->router = $router;
        $this->translator = $translator;
        $this->appmedia = $appmedia;
        $this->appmedia instanceof AppMedia;
        $this->scope = $scope;
        $this->feedenabled = $feedenabled;
        $this->namespace = $namespace;
    }

    /**
     * Get friends from facebook
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookFriends($user = null)
    {
        $friends = $this->api('/{uid}/friends', $user);
        if (isset($friends['data']))
            $friends = $friends['data'];
        return $friends;
    }

    /**
     * Get users that are facebook friends
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookFansworld($user = null, $limit = null, $offset = null)
    {
        $friends = $this->facebookFriends($user);
        if (!$friends)
            throw new \Exception('Sin amigos');
        $ids = array();
        foreach ($friends as $friend) {
            $ids[] = $friend['id'];
        }
        $friendrepo = $this->em->getRepository('Application\\Sonata\\UserBundle\\Entity\\User');
        $fwfriends = $friendrepo->findBy(
                array('enabled' => true, 'linkfacebook' => true, 'facebookId' => $ids), array('lastname' => 'ASC', 'firstname' => 'ASC'), $limit, $offset
        );
        return $fwfriends;
    }

    /**
     * Get facebook friends that are not Fansworld
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function facebookNotFansworld($user = null)
    {
        $friends = $this->facebookFriends($user);
        if (!$friends)
            throw new \Exception('Sin amigos');

        $fbFriendsIds = array();
        foreach ($friends as $friend) {
            $fbFriendsIds[] = $friend['id'];
        }

        $friendrepo = $this->em->getRepository('Application\\Sonata\\UserBundle\\Entity\\User');
        $fwfriends = $friendrepo->findBy(
                array('enabled' => true, 'linkfacebook' => true, 'facebookId' => $fbFriendsIds)
        );

        $fwFriendsIds = array();
        foreach ($fwfriends as $friend) {
            $fwFriendsIds[] =  $friend->getFacebookId();
        }

        $fbNotfw = array();
        foreach ($friends as $friend) {
            if (!in_array($friend['id'], $fwFriendsIds)) {
                $fbNotfw[] = $friend;
            }
        }

        // $fbNotfw = array_diff($fbFriendsIds, $fwFriendsIds);
        return $fbNotfw;
    }

    public function upload($entity)
    {
        if (!$this->feedenabled) return false;

        if (!property_exists($entity, 'author'))
            throw new \Exception('La entidad no es compatible');
        $user = $entity->getAuthor();
        if (!($user instanceof User))
            throw new \Exception('La entidad no tiene autor');

        $type = $this->getEntityType($entity);
        $url = $this->router->generate($type . '_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), true);
        //$message = $this->translator->trans('shared_' . $type) . ' ' . $url . ' #fansworlds';

        return $this->verb('upload', array(
            $type => $url
        ), $user);
    }

    public function subscribe($videocategory, $user)
    {
        $url = $this->router->generate(
        	'teve_explorechannel',
            array('id' => $videocategory->getId(), 'slug' => $videocategory->getSlug()),
            true
        );
        return $this->verb('subscribe', array('channel' => $url), $user);
    }

    public function comment($entity, $user)
    {
        $type = $this->getEntityType($entity);
        $url = $this->router->generate($type . '_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), true);
        return $this->verb('comment', array($type => $url), $user);
    }

    public function fan($entity, $user)
    {
        $type = $this->getEntityType($entity);
        if ($entity instanceof User) $params = array('username' => $entity->getUsername());
        else $params = $params = array('slug' => $entity->getSlug());
        $url = $this->router->generate($type . '_land', $params, true);
        return $this->verb('comment', array($type => $url), $user);
    }
    
    public function like($entity, $user)
    {
        $type = $this->getEntityType($entity);
        $url = $this->router->generate($type . '_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), true);
        
        $params = array('object' => $url);
        return $this->verb('og.likes', $params, $user, false);
    }

    public function verb($verb, $params, $user, $usenamespace=true)
    {
        if (!$this->feedenabled) return false;
        try {
            return $this->api('/{uid}/'.($usenamespace ? ($this->namespace.':') : '').$verb, $user, 'POST', $params);
        } catch (\Exception $e) {
            // do something
            return false;
        }
        //return $this->api('/{uid}/' . $verb, $user, 'POST', $params);
    }

    public function entityShare($entity, $message)
    {
        if (!$this->feedenabled) return false;

        $type = $this->getEntityType($entity);
        $url = $this->router->generate($type . '_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), true);

        $picture = null;
        if (property_exists($entity, 'image')) {
            $picture = $this->appmedia->getImageUrl($entity->getImage(), 'medium');
        }

        $data = array(
            'message' => $message,
            'name' => 'Fansworld ' . ucfirst($type),
            'redirect_uri' => $url,
            'caption' => 'Fansworld TV',
            'link' => $url,
            'description' => $entity->getContent()
        );

        if ($picture) $data['picture'] = $picture;

        return $this->api('/me/feed', null, 'POST', $data);
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getType($type)
    {
        return $this->namespace.':'.$type;
    }

    private function api($url, $user = null, $method = 'GET', $params = array())
    {
        if (!$user) {
            $user = $this->user;
        }
        if (!($user instanceof User))
            throw new \Exception('Falta usuario');
        if (!$user->getFacebookId())
            throw new \Exception('Usuario sin ID Facebook');

        $url = str_replace('{uid}', $user->getFacebookId(), $url);
        return $this->facebook->api($url, $method, $params);
    }

    private function getEntityType($entity)
    {
        $name = $this->em->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }

}