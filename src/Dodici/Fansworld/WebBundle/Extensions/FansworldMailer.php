<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Notification;


class FansworldMailer
{
    protected $mailer;
    protected $templating;     
    protected $request;
    protected $user;
    const MAIL_FROM = 'info@fansworld.tv';
    
    public function __construct($mailer,$templating,$security_context){
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->request = Request::createFromGlobals();
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
    }
    
    public function send($sendTo, $subject, $body) {    	
        $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom(self::MAIL_FROM)
                ->setTo($sendTo)
                ->setBody(trim(strip_tags($body)))
                ->addPart($body, 'text/html');
        return $this->mailer->send($message);
    }
    
    public function sendNotification($entity) {
        $user = $entity->getTarget();
        $sendTo = $user->getEmail();
		$allowed = $user->getNotifyprefs();
		
		if (in_array($entity->getType(), $allowed)) {
			
			$html = $this->templating->render('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $entity));
			$subject = substr(trim(strip_tags($html)), 0, 75);
			
			$html = str_replace(
				array('href="/','src="/'),
				array(
					'href="http://'.$this->request->getHost().'/',
					'src="http://'.$this->request->getHost().'/',
				), $html);
				
			$html = $this->templating->render('DodiciFansworldWebBundle:Mail:new_notification.html.twig', array('content' => $html));
			return $this->send($sendTo, $subject, $html);
		}
    }
}