<?php

namespace Dodici\Fansworld\WebBundle\Security;

use Dodici\Fansworld\WebBundle\Entity\Friendship;

use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use \BaseFacebook;
use \FacebookApiException;
use Gedmo\Sluggable\Util\Urlizer as GedmoUrlizer;

class FacebookProvider implements UserProviderInterface
{
    /**
     * @var \Facebook
     */
    protected $facebook;
    protected $userManager;
    protected $validator;
    protected $container;

    public function __construct(BaseFacebook $facebook, $userManager, $validator, $container)
    {
        $this->facebook = $facebook;
        $this->userManager = $userManager;
        $this->validator = $validator;
        $this->container = $container;
    }

    public function supportsClass($class)
    {
        return $this->userManager->supportsClass($class);
    }

    public function findUserByFbId($fbId)
    {
        return $this->userManager->findUserBy(array('facebookId' => $fbId));
    }

    public function loadUserByUsername($username)
    {
        $user = $this->findUserByFbId($username);

        try {
            $fbdata = $this->facebook->api('/me');
        } catch (FacebookApiException $e) {
            $fbdata = null;
        }
        
        if (!empty($fbdata)) {
        	if (empty($user)) {
                $user = $this->userManager->createUser();
                $user->setEnabled(true);
                $user->setConfirmationToken(null);
                $user->setLinkfacebook(true);
                $user->setPassword('');
                
                // Set FB image
		        $imagecontent = file_get_contents(sprintf('https://graph.facebook.com/%1$s/picture', $fbdata['id']));
				if ($imagecontent) {
					$tmpfile = tempnam('/tmp', 'IFB');
					file_put_contents($tmpfile, $imagecontent);
					$mediaManager = $this->container->get("sonata.media.manager.media");
			        $media = new Media();
			        $media->setBinaryContent($tmpfile);
			        $media->setContext('default');
			        $media->setProviderName('sonata.media.provider.image');
			        $mediaManager->save($media);
			        $user->setImage($media); 
				}
				
				if (isset($fbdata['location']) && $fbdata['location']) {
					$location = $this->container->get('user.location')->parseLocation($fbdata['location']);
					$user->setCountry($location['country']);
					$user->setCity($location['city']);
				}
				
				$user->setFBData($fbdata);
				
				if ($user->getFirstname() || $user->getLastname()) {
					$username = GedmoUrlizer::urlize($user->getFirstname() . ' ' . $user->getLastname());
					if ($this->userManager->findUserBy(array('username' => $username))) {
						$username = $username.uniqid();
					}
				} elseif ($fbdata['id']) {
					$username = $fbdata['id'];
					if ($this->userManager->findUserBy(array('username' => $username))) {
						$username = $username.uniqid();
					}
				} else {
					$username = uniqid();
				}
				
				$user->setUsername($username);
				
        		/* Invitation, check for session */
				$session = $this->container->get('session');
				$invitetoken = $session->get('registration.inviter');
        		$inviteuser = $session->get('registration.token');
        		$inviter = null;
        		if ($invitetoken && $inviteuser) {
	        		$userrepo = $this->container->get('doctrine')->getRepository('Application\Sonata\UserBundle\Entity\User');
	        		$inviter = $userrepo->findOneByUsername($inviteuser);
	        		$calcinvitetoken = $this->container->get('contact.importer')->inviteToken($inviter);
	        		if ($inviter && ($invitetoken != $calcinvitetoken)) {
	        			$this->container->get('contact.importer')->finalizeInvitation($inviter, $user, false);
	        		}
        		}
            }

            // TODO use http://developers.facebook.com/docs/api/realtime
            
            if (count($this->validator->validate($user, 'Facebook'))) {
                // TODO: the user was found obviously, but doesnt match our expectations, do something smart
                throw new UsernameNotFoundException('The facebook user could not be stored');
            }
            $this->userManager->updateUser($user);
        }

        if (empty($user)) {
            throw new UsernameNotFoundException('The user is not authenticated on facebook');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user)) || !$user->getFacebookId()) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getFacebookId());
    }
}