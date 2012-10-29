<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Eventship;
use Dodici\Fansworld\WebBundle\Entity\EventTweet;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Dodici\Fansworld\WebBundle\Entity\EventIncident;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Dodici\Fansworld\WebBundle\Entity\Notification;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Meteor
{
	protected $request;
	protected $em;
	protected $appstate;
	protected $host;
	protected $port;
	protected $clientport;
	protected $socket;
	protected $debugmode;
	protected $enabled;

    function __construct(EntityManager $em, $appstate, $host='127.0.0.1', $port='4671', $clientport='4670', $debugmode=true, $enabled=true)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->appstate = $appstate;
        $this->host = $host;
        $this->port = $port;
        $this->clientport = $clientport;
        $this->debugmode = $debugmode;
        $this->enabled = $enabled;
    }

    /**
     * Push a notification/friendship onto the meteor server
     * @param $entity
     */
    public function push($entity)
    {
    	if ($entity instanceof Notification || $entity instanceof Friendship) {
	    	return $this->sendToSocket(
	    	    array(
	    	    	't' => (($entity instanceof Notification) ? 'n' : 'f'), 
	    	    	'id' => $entity->getId()
	    	    ), 
	    	    $this->encryptChannelName('notification', $entity->getTarget())
	    	);
    	} elseif ($entity instanceof Comment) {
    	    $possiblewalls = array(
    	        $entity->getTarget(), $entity->getVideo(), $entity->getPhoto(), $entity->getAlbum(), $entity->getContest(), $entity->getEvent(),
    	        $entity->getMeeting(), $entity->getComment(), $entity->getTeam(), $entity->getIdol()
    	    );
    	    
    	    $wallname = null;
    	    
    	    foreach ($possiblewalls as $pw) {
    	        if ($pw) {
    	            $wallname = $this->appstate->getType($pw) . '_' . $pw->getId();
    	            break;
    	        }
    	    }
    	    
    	    if ($wallname) {
        	    $data = array('t' => 'c', 'w' => $wallname, 'id' => $entity->getId());
        	    if ($entity->getComment()){ 
        	    	$data['p'] = $entity->getComment()->getId();
        	    }
        	    return $this->sendToSocket($data, 'wall_'.$wallname);
    	    } else {
    	        throw new \Exception('Could not form wall channel name for comment - no owner entity found');
    	    }
    	} elseif ($entity instanceof EventIncident) {
    	    $data = array(
    	        't' => 'ei',
    	        'id' => $entity->getId()
    	    );
        	    
        	return $this->sendToSocket($data, 'event_'.$entity->getEvent()->getId());
    	} elseif ($entity instanceof EventTweet) {
    	    $data = array(
    	        't' => 'et',
    	        'id' => $entity->getId()
    	    );
        	    
        	return $this->sendToSocket($data, 'event_'.$entity->getEvent()->getId());
        } else {
    		return false;
    	}
    }

    public function addEventship(Event $event, User $author)
    {
        $data = array(
            't' => 'ea',
            'a' => 'a',
            'id' => $author->getId()
        );
        
        return $this->sendToSocket($data, 'eventship_'.$event->getId());
    }

    public function removeEventship(Eventship $eventship)
    {
        $data = array(
            't' => 'ea',
            'a' => 'r',
            'id' => $eventship->getAuthor()->getId()
        );
        
        return $this->sendToSocket($data, 'eventship_'.$eventship->getEvent()->getId());
    }
    
    public function addUserWatchingVideo(Video $video, User $user)
    {
        $data = array(
            't' => 'va',
            'a' => 'a',
            'id' => $user->getId()
        );
        
        return $this->sendToSocket($data, 'videoaudience_'.$video->getId());
    }
    
    public function removeUsersWatchingVideo(Video $video, $users)
    {
        $userids = array();
        foreach ($users as $user) $userids[] = $user->getId();
        
        $data = array(
            't' => 'va',
            'a' => 'r',
            'id' => $userids
        );
        
        return $this->sendToSocket($data, 'videoaudience_'.$video->getId());
    }
    
    public function encryptChannelName($channel, User $user)
    {
    	return $channel.'_'.sha1($user->getId().'hfd78has7'.$channel);
    }
    
    public function getHost()
    {
    	return $this->host;
    }
    
    public function getControllerPort()
    {
    	return $this->port;
    }
    
    public function getClientPort()
    {
    	return $this->clientport;
    }
    
    public function getUniqid()
    {
    	return uniqid();
    }
    
    public function getPing()
    {
        $errno = null; $errstr = null;
        $socket = @fsockopen($this->host, $this->clientport, $errno, $errstr, 0.5);
        if ($socket) return true;
        else return false;
    }
    
    public function getEnabled()
    {
        return $this->enabled;
    }
    
    public function getDebugMode()
    {
        return $this->debugmode ? 'true' : 'false';
    }
    
    private function sendToSocket($message, $channel)
    {
    	if (!$this->socket) {
            $this->socket = @fsockopen($this->host, $this->port);
    	}
        
    	if ($this->socket) {
    		stream_set_blocking($this->socket,true);
    	    $message = addslashes(json_encode($message));
    		$out = "ADDMESSAGE ".$channel." ".$message."\n";
    		fwrite($this->socket, $out);
    		return true;
    	}
    	return false;
    }
    
}