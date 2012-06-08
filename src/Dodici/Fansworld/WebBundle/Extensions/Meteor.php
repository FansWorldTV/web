<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Dodici\Fansworld\WebBundle\Entity\Notification;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Meteor
{
	protected $request;
	protected $em;
	protected $host;
	protected $port;
	protected $clientport;

    function __construct(EntityManager $em, $host='127.0.0.1', $port='4671', $clientport='4670')
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
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
    	if ($entity instanceof Notification) {
	    	return $this->sendToSocket(array('t' => 'n', 'id' => $entity->getId()), $this->encryptChannelName('notification', $entity->getTarget()));
    	} elseif ($entity instanceof Friendship) {
    		return $this->sendToSocket(array('t' => 'f', 'id' => $entity->getId()), $this->encryptChannelName('notification', $entity->getTarget()));
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
    	$op = fsockopen($this->host, $this->port);
    	socket_set_blocking($op,false);
        
    	if ($op) {
    		$message = addslashes(json_encode($message));
    		$out = "ADDMESSAGE ".$channel." ".$message."\n";
    		fwrite($op, $out);
    		return true;
    	}
    	return false;
    }
    
}