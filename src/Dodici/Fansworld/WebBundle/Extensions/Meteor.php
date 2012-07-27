<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

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

    function __construct(EntityManager $em, $appstate, $host='127.0.0.1', $port='4671', $clientport='4670')
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->appstate = $appstate;
        $this->host = $host;
        $this->port = $port;
        $this->clientport = $clientport;
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
    	        $entity->getMeeting(), $entity->getComment()
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
    	} else {
    		return false;
    	}
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