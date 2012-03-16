<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Symfony\Component\HttpFoundation\Request;

use Dodici\Fansworld\WebBundle\Entity\Notification;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

class NotificationMailer
{
    protected $container;
    protected $request;
    
	function __construct($container)
    {
        $this->container = $container;
        $this->request = Request::createFromGlobals();
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
				$html = $this->container->get('templating')->render('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $entity));
		
				$subject = substr(trim(strip_tags($html)), 0, 75);
				
				$html = str_replace(
					array('href="/','src="/'),
					array(
						'href="http://'.$this->request->getHost().'/',
						'src="http://'.$this->request->getHost().'/',
					), $html);
					
				$html = $this->container->get('templating')->render('DodiciFansworldWebBundle:Mail:new_notification.html.twig', array('content' => $html));
				
				$message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom('info@fansworld.tv')
                        ->setTo($user->getEmail())
                        ->setBody(trim(strip_tags($html)))
                        ->addPart($html, 'text/html');
                $sent = $mailer->send($message);
				
			}
		}
		
    }
    
}