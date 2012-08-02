<?php
namespace Dodici\Fansworld\WebBundle\Listener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;

/**
 * Posts user activity to Facebook's timeline, if the user has it enabled
 */
class FacebookActivity
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
			if ($user->getLinkfacebook() && $entity->getActive()){
                $this->container->get('app.facebook')->upload($entity);
			} 
		}
		
    	if ($entity instanceof Video && $entity->getAuthor()) {
			$user = $entity->getAuthor();
    	    if ($user->getLinkfacebook() && $entity->getActive()){
			    $this->container->get('app.facebook')->upload($entity);
			}
		}
    }
}