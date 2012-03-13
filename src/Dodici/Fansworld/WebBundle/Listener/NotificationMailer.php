<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Notification;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

class NotificationMailer
{
    protected $container;
    
	function __construct($container)
    {
        $this->container = $container;
    }
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
    	if ($entity instanceof Notification) {
			$user = $entity->getTarget();
			$allowed = $user->getNotifyprefs();
			if (in_array($entity->getType(), $allowed)) {
				$mailer = $this->container->get('mailer');
				$message = \Swift_Message::newInstance()
                        ->setSubject('[FANSWORLD] Nueva notificación')
                        ->setFrom('info@fansworld.tv')
                        ->setTo($user->getEmail())
                        ->setBody('(TODO)');
                        //->setBody($this->container->get('templating')->render('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $entity)));
                $sent = $mailer->send($message);
			}
		}
		
    }
    
}