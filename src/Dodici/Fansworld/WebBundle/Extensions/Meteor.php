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

    function __construct(EntityManager $em, $host='127.0.0.1', $port='4671')
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Push a notification/friendship onto the meteor server
     * @param $entity
     */
    public function push($entity)
    {
    	if ($entity instanceof Notification) {
	    	$this->sendToSocket($entity->getId(), $this->encryptChannelName('notification', $entity->getTarget()));
    	} elseif ($entity instanceof Friendship) {
    		$this->sendToSocket($entity->getId(), $this->encryptChannelName('friendship', $entity->getTarget()));
    	} else {
    		return false;
    	}
    }
    
    private function encryptChannelName($channel, User $user)
    {
    	return $channel.'_'.sha1($user->getId().'hfd78has7');
    }
    
    private function sendToSocket($message, $channel)
    {
    	$op = fsockopen($this->host, $this->port);
    	socket_set_blocking($op,false);
    	if ($op) {
    		$message = json_encode($message);
    		$out = "ADDMESSAGE ".$channel." ".$message."\n";
    		fwrite($op, $out);
    		return true;
    	}
    	return false;
    }
    
}