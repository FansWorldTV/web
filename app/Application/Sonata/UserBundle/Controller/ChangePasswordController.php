<?php

namespace Application\Sonata\UserBundle\Controller;

use FOS\UserBundle\Controller\ChangePasswordController as BaseController;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

class ChangePasswordController extends BaseController {

    public function changePasswordAction() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        if ($user->getFacebookId() && $user->getPassword() == '') {
            $form = $this->container->get('form.factory')->createBuilder('form', array())
                    ->add('password', 'repeated', array('type' => 'password', 'required' => true))
                    ->getForm();

            $request = $this->container->get('request');
            if ($request->getMethod() == 'POST') {
                $form->bindRequest($request);
                if ($form->isValid()) {
                    $data = $form->getData();
                    $user->setPlainPassword($data['password']);
                    $this->container->get('fos_user.user_manager')->updatePassword($user);

                    try {
                        $em = $this->container->get('sonata.media.entity_manager');
                        $em->persist($user);
                        $em->flush();
                        
                        return new RedirectResponse($this->getRedirectionUrl($user));
                    } catch (Exception $exc) {
                        $form->addError(new FormError($exc->getMessage()));
                    }
                } else {
                    $form->addError(new FormError('Las contraseñas no coinciden'));
                }
            }
        } else {
            $form = $this->container->get('fos_user.change_password.form');
            $formHandler = $this->container->get('fos_user.change_password.form.handler');

            $process = $formHandler->process($user);
            if ($process) {
                $this->setFlash('success', 'Se ha guardado su nueva contraseña');

                return new RedirectResponse($this->getRedirectionUrl($user));
            }
        }

        return $this->container->get('templating')->renderResponse(
                        'FOSUserBundle:ChangePassword:changePassword.html.' . $this->container->getParameter('fos_user.template.engine'), array('form' => $form->createView(), 'theme' => $this->container->getParameter('fos_user.template.theme'))
        );
    }

    /**
     * Override to redirect to the profile edit page instead
     */
    protected function getRedirectionUrl(UserInterface $user) {
        return $this->container->get('router')->generate('fos_user_profile_edit');
    }

}
