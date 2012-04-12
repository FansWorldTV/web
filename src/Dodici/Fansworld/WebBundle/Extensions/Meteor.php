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
	protected $templating;
	protected $mediapool;
	protected $router;
	protected $host;
	protected $port;

    function __construct(EntityManager $em, $templating, $mediapool, $router, $host='127.0.0.1', $port='4671')
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->templating = $templating;
        $this->mediapool = $mediapool;
        $this->router = $router;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Push a notification/friendship onto the meteor server
     * @param $entity
     */
    public function push($entity)
    {
    	$op = fsockopen($this->host, $this->port);
    	socket_set_blocking($op,false);
    	if ($op) {
    		
    		if ($entity instanceof Notification) {
	    		$channel = 'notification';
    			$html = $this->templating->render('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $entity));
	    		$message = json_encode($html);
    		} elseif ($entity instanceof Friendship) {
    			$channel = 'friendship';
    			$media = $entity->getAuthor()->getImage();
    			$fs = array(
                    'friendship' => array(
                        'id' => $entity->getId(),
                        'ts' => $entity->getCreatedat()->format('U')
                    ),
                    'user' => array(
                        'id' => $entity->getAuthor()->getId(),
                        'name' => (string) $entity->getAuthor(),
                        'image' => $this->getImageUrl($media),
                        'url' => $this->router->generate('user_detail', array('id' => $entity->getAuthor()->getId()))
                    )
                );
                $message = json_encode($fs);
    		} else {
    			return false;
    		}
    		
    		$out = "ADDMESSAGE ".$channel." ".$message."\n";
    		
    		fwrite($op, $out);
    		return $out;
    	}
    }
    
    private function getImageUrl($media, $sizeFormat = 'small')
    {
        $imageUrl = null;
        $request = $this->request;
        $mediaService = $this->mediapool;

        $host = 'http://' . $request->getHost();

        if ($media) {
            $provider = $mediaService->getProvider($media->getProviderName());

            $format = $provider->getFormatName($media, $sizeFormat);
            $imageUrl = $provider->generatePublicUrl($media, $format);
            
            return $host . $imageUrl;
        }
        
        return false;
    }
    
}