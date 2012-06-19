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

    public function __construct($mailer, $templating, $security_context)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->request = Request::createFromGlobals();
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
    }

    public function send($sendTo, $subject, $body)
    {
        $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom(self::MAIL_FROM)
                ->setTo($sendTo)
                ->setBody(trim(strip_tags($body)))
                ->addPart($body, 'text/html');
        return $this->mailer->send($message);
    }

    public function sendNotification($entity)
    {
        $user = $entity->getTarget();
        $sendTo = $user->getEmail();
        $allowed = $user->getNotifyprefs();

        if (in_array($entity->getType(), $allowed)) {
            $type_templates = array(
                Notification::TYPE_COMMENT_ANSWERED => 'comment_answered',
                Notification::TYPE_FORUM_ANSWERED => 'forum_answered',
                Notification::TYPE_FORUM_CREATED => 'forum_created',
                Notification::TYPE_FRIENDSHIP_ACCEPTED => 'friendship_accepted',
                Notification::TYPE_USER_TAGGED => 'user_tagged'
            );

            $html = $this->templating->render('DodiciFansworldWebBundle:Notification:Mail/' . $type_templates[$entity->getType()] . '.html.twig', array('notification' => $entity));
            $exploded = explode("\n", $html);
            $subject = substr($exploded[6], 19, -8);

            return $this->send($sendTo, $subject, $html);
        }
    }

}