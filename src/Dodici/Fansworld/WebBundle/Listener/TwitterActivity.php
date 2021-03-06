<?php
namespace Dodici\Fansworld\WebBundle\Listener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;

/**
 * Posts activity to the user's twitter, if it's enabled
 */
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
			if ($user->getLinktwitter() && $entity->getActive()){
			    $upload =  $this->container->get('app.twitter')->upload($entity); 
			} 
		}
		
    	if ($entity instanceof Video && $entity->getAuthor()) {
			$user = $entity->getAuthor();
    	    if ($user->getLinktwitter() && $entity->getActive()){
			    $this->container->get('app.twitter')->upload($entity);
			}
		}
    }
}