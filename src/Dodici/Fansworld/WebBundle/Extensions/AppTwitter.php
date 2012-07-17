<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class AppTwitter
{

    protected $security_context;
    protected $request;
    protected $em;
    protected $user;
    protected $twitter;
    protected $appstate;
    protected $router;
    protected $translator;

    function __construct(SecurityContext $security_context, EntityManager $em, $twitter, $appstate, $router, $translator)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        $this->twitter = $twitter;
        $this->appstate = $appstate;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * Get friends/is following from twitter
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function twitterFriends($user = null)
    {
        $friends = $this->api('friends/ids', $user, 'GET', array('user_id' => $user->getTwitterid()));
        if (isset($friends['ids']))
            $friends = $friends['ids'];
        return $friends;
    }

    /**
     * Get users that are twitter friends/is following
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function twitterFansworld($user = null, $limit = null, $offset = null)
    {
        $friends = $this->facebookFriends($user);
        if (!$friends)
            throw new \Exception('Sin amigos');

        $friendrepo = $this->em->getRepository('translator');
        $ttfriends = $friendrepo->findBy(
                array('enabled' => true, 'linktwitter' => true, 'twitterId' => $friends), array('lastname' => 'ASC', 'firstname' => 'ASC'), $limit, $offset
        );
        return $ttfriends;
    }

    /**
     * Get followers from twitter
     * @param Application\Sonata\UserBundle\Entity\User $user
     * @throws \Exception
     */
    public function twitterFollowers($user = null)
    {
        $followers = $this->api('followers/ids', $user, 'GET', array('user_id' => $user->getTwitterid()));
        if (isset($followers['ids']))
            $followers = $followers['ids'];
        return $followers;
    }

    public function upload($entity)
    {
        if (!property_exists($entity, 'author'))
            throw new \Exception('La entidad no es compatible');
        $user = $entity->getAuthor();
        if (!($user instanceof User))
            throw new \Exception('La entidad no tiene autor');

        $type = $this->appstate->getType($entity);
        $url = $this->router->generate($type . '_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug()), true);
        $message = $this->translator->trans('shared_' . $type) . ' ' . $url . ' #fansworlds';

        $params = array(
            'status' => $message
        );
        return $this->api('statuses/update', $user, 'POST', $params);
    }

    /*
      public function verb($verb, $params, $user)
      {
      return $this->api('/{uid}/fansworld:'.$verb, $user, 'POST', $params);
      }
     */

    private function api($url, $user = null, $method = 'GET', $params = array())
    {
        if (!$user) {
            $user = $this->user;
        }
        if (!($user instanceof User))
            throw new \Exception('Falta usuario');
        if (!$user->getTwitterId())
            throw new \Exception('Usuario sin ID Twitter');
        if (!$user->getTwittertoken() || !$user->getTwittersecret())
            throw new \Exception('Usuario sin cuenta de Twitter relacionada');

        $this->twitter->setOAuthToken($user->getTwittertoken(), $user->getTwittersecret());

        if ($method == 'POST') {
            return $this->twitter->post($url, $params);
        } else if ($method == 'GET') {
            return $this->twitter->get($url, $params);
        }


        //return $this->twitter->api($url, $method, $params);
    }

}