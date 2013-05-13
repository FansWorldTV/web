<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Notification;

class FansworldMailer
{

  protected $mailer;
  protected $templating;
  protected $request;
  protected $user;
  protected $translator;

  const MAIL_FROM = 'info@fansworld.tv';

  public function __construct($mailer, $templating, $security_context, $translator)
  {
    $this->mailer = $mailer;
    $this->templating = $templating;
    $this->request = Request::createFromGlobals();
    $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
    $this->translator = $translator;
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
    $allowed = $user->getNotifymail();

    if (in_array($entity->getType(), $allowed)) {
      $typename = $entity->getTypeName();

      $html = $this->templating->render('DodiciFansworldWebBundle:Notification:Mail/' . $typename . '.html.twig', array('notification' => $entity, 'targetUser' => $entity->getTarget()));

      if (in_array($entity->getType(), array(Notification::TYPE_VIDEO_PROCESSED, Notification::TYPE_VIDEO_SUBSCRIPTION))) {
        $params['%video%'] = (string) $entity->getVideo();
      } else {
        $params['%author%'] = (string) $entity->getAuthor();
      }

      $subject = $this->translator->trans(
              'notification_' . $typename, $params
      );

      return $this->send($sendTo, $subject, $html);
    }
  }

  public function sendWelcome($user)
  {
      $sendTo = $user->getEmail();

      $html = $this->templating->render('DodiciFansworldWebBundle:Mail:welcome.html.twig', array('targetUser' => $user));
      
      $subject = $this->translator->trans('Bienvenido a Fansworld.tv');

      return $this->send($sendTo, $subject, $html);
  }  

}