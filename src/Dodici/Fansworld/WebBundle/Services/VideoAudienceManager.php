<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Dodici\Fansworld\WebBundle\Entity\VideoAudience;

use Dodici\Fansworld\WebBundle\Entity\Video;

use Symfony\Component\Security\Core\SecurityContext;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Handles the audience list of a video, removal, adding, listing, and pushing to Meteor
 */
class VideoAudienceManager
{
	// How long before we remove a user from the list, that hasn't updated via keepalive
    const TIMEOUT = '-5 min';
    
    protected $security_context;
    protected $em;
    protected $user;
    protected $meteor;

    function __construct(SecurityContext $security_context, EntityManager $em, $meteor)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->meteor = $meteor;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Get users watching the video
     * @param Video $video
     * @param User|null $user
     */
    public function watching(Video $video, User $user=null)
    {
        $user = $user ?: $this->user;
        
        $varepo = $this->em->getRepository('DodiciFansworldWebBundle:VideoAudience');
        
        $userswatching = $varepo->watching($video, $user);
        
        return $userswatching;
    }
    
    /**
     * Add a user to the list of watchers
     * @param Video $video
     * @param User|null $user
     */
    public function join(Video $video, User $user=null)
    {
        $user = $user ?: $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        $varepo = $this->em->getRepository('DodiciFansworldWebBundle:VideoAudience');
        
        $exists = $varepo->findOneBy(array('video' => $video->getId(), 'author' => $user->getId()));
        
        if (!$exists) {
            $va = new VideoAudience();
            $va->setAuthor($user);
            $va->setVideo($video);
            
            $this->em->persist($va);
            $this->em->flush();
            
            $this->meteor->addUserWatchingVideo($video, $user);
        } else {
            $this->keepalive($video, $user);
        }
    }  
    
    /**
     * Keepalive signal, to keep the user in the watchers list
     * @param Video $video
     * @param User|null $user
     */
    public function keepalive(Video $video, User $user=null)
    {
        $user = $user ?: $this->user;
        if (!$user) throw new AccessDeniedException('Access denied');
        
        $varepo = $this->em->getRepository('DodiciFansworldWebBundle:VideoAudience');
        $videoaudience = $varepo->findOneBy(array('video' => $video->getId(), 'author' => $user->getId()));
        
        if ($videoaudience) {
            $videoaudience->setUpdatedAt(new \DateTime());
            
            $this->em->persist($videoaudience);
            $this->em->flush();
        } else {
            $this->join($video, $user);
        }
        
        return true;
    }
    
    /**
     * Clean up timed out users in all watcher list
     */
    public function cleanup()
    {
        $varepo = $this->em->getRepository('DodiciFansworldWebBundle:VideoAudience');
        $timedouts = $varepo->timedOut(new \DateTime(self::TIMEOUT));
        
        $timedoutusers = array();
        $videos = array();
        foreach ($timedouts as $to) {
            $timedoutusers[$to->getVideo()->getId()][$to->getAuthor()->getId()] = $to->getAuthor();
            if (!isset($videos[$to->getVideo()->getId()])) $videos[$to->getVideo()->getId()] = $to->getVideo();
            $this->em->remove($to);
        }
        $this->em->flush();
        
        foreach ($timedoutusers as $idvideo => $tousers) {
            $this->meteor->removeUsersWatchingVideo($videos[$idvideo], $tousers);
        }
    }
}