<?php

/*
 * This file is part of the FOSTwitterBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\TwitterBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\DependencyInjection\Container;

use FOS\TwitterBundle\Security\Authentication\Token\TwitterUserToken;
use FOS\TwitterBundle\Services\Twitter;

class TwitterProvider implements AuthenticationProviderInterface
{
    protected $twitter;
    protected $accessToken;
    protected $userProvider;
    protected $userChecker;
    protected $container;

    public function __construct(Twitter $twitter, Container $container, UserProviderInterface $userProvider = null, UserCheckerInterface $userChecker = null)
    {
        if (null !== $userProvider && null === $userChecker) {
            throw new \InvalidArgumentException('$userChecker cannot be null, if $userProvider is not null.');
        }
        $this->twitter = $twitter;
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->container = $container;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        try {
            if ($this->accessToken = $this->twitter->getAccessToken($this->container->get('request'))) {
                return $this->createAuthenticatedToken($this->accessToken['user_id']);
            }
        } catch (AuthenticationException $failed) {
            throw $failed;
        } catch (\Exception $failed) {
            throw new AuthenticationException('Unknown error', $failed->getMessage(), $failed->getCode(), $failed);
        }

        throw new AuthenticationException('The Twitter user could not be retrieved from the session.');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof TwitterUserToken;
    }

    protected function createAuthenticatedToken($uid)
    {
        if (null === $this->userProvider) {
            return new TwitterUserToken($uid);
        }

        $user = $this->userProvider->loadUserByUsername($uid);
        if (!$user instanceof UserInterface) {
            throw new \RuntimeException('User provider did not return an implementation of user interface.');
        }

        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        return new TwitterUserToken($user, $user->getRoles());
    }
}
