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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\SecurityExtraBundle\Annotation\Secure;

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
            $session = $this->container->get('session');
            $inviteuser = $request->get('inviter');
            $invitetoken = $request->get('token');
            if (!$inviteuser || !$invitetoken) {
                $inviteuser = $session->get('registration.inviter');
                $invitetoken = $session->get('registration.token');
            }
            $inviter = null;

            $userrepo = $this->container->get('doctrine')->getRepository('Application\Sonata\UserBundle\Entity\User');
            if ($invitetoken && $inviteuser) {
                $inviter = $userrepo->findOneByUsername($inviteuser);
                if (!$inviter)
                    throw new \Exception('No existe el usuario invitador');
                $calcinvitetoken = $this->container->get('contact.importer')->inviteToken($inviter);
                if ($invitetoken != $calcinvitetoken)
                    throw new \Exception('Código de invitación inválido');

                $session->set('registration.inviter', $inviteuser);
                $session->set('registration.token', $invitetoken);
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
                    $fbrequest = $session->get('registration.fbrequest');
                    $this->container->get('contact.importer')->finalizeInvitation($inviter, $user, true, $fbrequest);
                }

                $this->setFlash('fos_user_success', 'registration.flash.user_created');
                $url = $this->container->get('router')->generate($route);

                return new RedirectResponse($url);
            }

            return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:register.html.' . $this->getEngine(), array(
                        'form' => $form->createView(),
                        'theme' => $this->container->getParameter('fos_user.template.theme'),
                        'inviter' => $inviter
                    ));
        }
    }

	/**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction($token)
    {

        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }
        
        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $user->setLastLogin(new \DateTime());

        // override: set expiresat = null
        $user->removeExpireDate();

        $this->container->get('fos_user.user_manager')->updateUser($user);
        $this->authenticateUser($user);
   
        // $this->container->get('fansworldmailer')->sendWelcome($user); ignore for now

        return new RedirectResponse($this->container->get('router')->generate('homepage'));
    }
}
