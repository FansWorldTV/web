<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Friendship;

use Application\Sonata\UserBundle\Entity\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;
use Artseld\OpeninviterBundle\ArtseldOpeninviter\ArtseldOpeninviter;

class ContactImporter
{
    protected $request;
    protected $container;
    protected $inviter;

    function __construct(Container $container)
    {
        $this->request = Request::createFromGlobals();
        $this->container = $container;
        /*$this->inviter = new ArtseldOpeninviter( $this->container );*/
    }
      
    /**
     * Generate a token for the invite users url
     * @param User $user
     */
    public function inviteToken(User $user)
    {
        return sha1($user->getId().'-ashurbanipal-'.$user->getUsername());
    }
    
    public function inviteUrl(User $user)
    {
        $router = $this->container->get('router');
        return $router->generate(
            'fos_user_registration_register', 
            array(
                'inviter' => $user->getUsername(), 
                'token' => $this->inviteToken($user)
            ), true);
    }
    
    public function finalizeInvitation(User $inviter, User $target, $flush=true, $fbrequest=null)
    {
        if ($fbrequest && $target->getFacebookId()) {
            foreach ($fbrequest as $fbr) {
                $fullid = $fbr.'_'.$target->getFacebookId();
                try {
                    $this->container->get('app.facebook')->delete($fullid);
                } catch(\Exception $e) {
                    // failed to delete request
                }
            }
        }
        
        return $this->container->get('friender')->friend($inviter, null, $target, true);
    }
}