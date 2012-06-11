<?php
namespace Dodici\Fansworld\WebBundle\Listener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;

class TwitterActivity
{
    protected $container;
    
    public function __construct( $container)
    {
        $this->container = $container;
    }
    
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        
        $entity = $eventArgs->getEntity();
        
		$em = $eventArgs->getEntityManager();
		
        if ($entity instanceof Photo && $entity->getAuthor()) {
			$user = $entity->getAuthor();
			if ($user->getTwitterId() && $entity->getActive()){
			    $upload =  $this->container->get('app.twitter')->upload($entity);
			    var_dump($upload); 
			} 
		}
		
    	if ($entity instanceof Video && $entity->getAuthor()) {
			$user = $entity->getAuthor();
    	    if ($user->getTwitterId() && $entity->getActive()){
			    $this->container->get('app.twitter')->upload($entity);
			}
		}
    }
}