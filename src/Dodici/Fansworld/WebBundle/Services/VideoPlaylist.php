<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;
use Dodici\Fansworld\WebBundle\Entity\WatchLater;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class VideoPlaylist
{
	protected $request;
	protected $security_context;
	protected $em;
    protected $user;
    protected $appstate;

    function __construct(SecurityContext $security_context, EntityManager $em, $appstate)
    {
        $this->request = Request::createFromGlobals();
        $this->security_context = $security_context;
        $this->em = $em;
        $this->appstate = $appstate;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Create a video entry in the user's playlist
     * @param $entity
     */
    public function add(Video $video, $user = null)
    {
        if (!$user) $user = $this->user;
        if (!($user instanceof User)) throw new AccessDeniedException('Tried to add video to playlist with no user logged in');
        
        if (!$this->appstate->canView($video, $user)) throw new AccessDeniedException('User cannot view that video');
        
        $wl = $this->em->getRepository('DodiciFansworldWebBundle:WatchLater')->findOneBy(
            array('author' => $user->getId(), 'video' => $video->getId())
        );
        
        if (!$wl) {
            $wl = new WatchLater();
            $wl->setVideo($video);
            $wl->setAuthor($user);
    		
        	$this->em->persist($wl);
            $this->em->flush();
    		
    		return $wl;
        } else {
            return false;
        }
    }
    
	/**
     * Remove a video from the user's playlist
     * @param $entity
     */
    public function remove(Video $video, $user = null)
    {
        if (!$user) $user = $this->user;
        if (!($user instanceof User)) throw new AccessDeniedException('Tried to add video to playlist with no user logged in');
        
        if (!$this->appstate->canView($video, $user)) throw new AccessDeniedException('User cannot view that video');
        
        $wl = $this->em->getRepository('DodiciFansworldWebBundle:WatchLater')->findOneBy(
            array('author' => $user->getId(), 'video' => $video->getId())
        );
		
        if ($wl) {
        	$this->em->remove($wl);
            $this->em->flush();
        }
		
		return $wl;
    }
    
	/**
     * Get a user's video playlist
     * @param $entity
     */
    public function get($user=null, $limit=null, $offset=null, $sort = array('createdAt' => 'ASC'))
    {
        if (!$user) $user = $this->user;
        if (!($user instanceof User)) throw new AccessDeniedException('Invalid user for playlist');
        
        $wls = $this->em->getRepository('DodiciFansworldWebBundle:WatchLater')->findBy(
            array('author' => $user->getId()),
            $sort,
            $limit,
            $offset
        );
		
		return $wls;
    }
    
    public function isInPlaylist(Video $video, $user = null)
    {
        if (!$user) $user = $this->user;
        if (!($user instanceof User)) throw new AccessDeniedException('Invalid user for playlist');
        
        $wls = $this->em->getRepository('DodiciFansworldWebBundle:WatchLater')->findOneBy(
            array('author' => $user->getId(), 'video' => $video->getId())
        );
        
        return $wls ? true : false;
    }
}