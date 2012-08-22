<?php

namespace Application\Sonata\UserBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Controller\RegistrationController as BaseController;

class RegistrationController extends BaseController
{

    /**
     * Override, redirect if logged in, add invite code
     */
    public function registerAction()
    {
        if ($this->container->get('security.context')->getToken()->getUser() instanceof User) {
            $url = $this->container->get('router')->generate('homepage');
            return new RedirectResponse($url);
        } else {
            /* Invite code */
            $request = $this->container->get('request');
            $inviteuser = $request->get('inviter');
            $invitetoken = $request->get('token');
            $inviter = null;
            $userrepo = $this->container->get('doctrine')->getRepository('Application\Sonata\UserBundle\Entity\User');
            if ($invitetoken && $inviteuser) {
                $inviter = $userrepo->findOneByUsername($inviteuser);
                if (!$inviter)
                    throw new \Exception('No existe el usuario invitador');
                $calcinvitetoken = $this->container->get('contact.importer')->inviteToken($inviter);
                if ($invitetoken != $calcinvitetoken)
                    throw new \Exception('Código de invitación inválido');
                $session = $this->container->get('session');
                $session->set('registration.inviter', $invitetoken);
                $session->set('registration.token', $inviteuser);
            }

            $form = $this->container->get('fos_user.registration.form');
            $formHandler = $this->container->get('fos_user.registration.form.handler');
            $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

            $process = $formHandler->process($confirmationEnabled);
            if ($process) {
                $user = $form->getData();
                
                if ($confirmationEnabled) {
                    $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                    $route = 'fos_user_registration_check_email';
                } else {
                    $this->authenticateUser($user);
                    $route = 'fos_user_registration_confirmed';
                }

                if ($inviter) {
                    $this->container->get('contact.importer')->finalizeInvitation($inviter, $user);
                }

                $this->setFlash('fos_user_success', 'registration.flash.user_created');
                $url = $this->container->get('router')->generate($route);

                return new RedirectResponse($url);
            }

            return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:register.html.' . $this->getEngine(), array(
                        'form' => $form->createView(),
                        'theme' => $this->container->getParameter('fos_user.template.theme'),
                    ));
        }
    }

}
