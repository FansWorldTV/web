<?php

namespace Application\Sonata\UserBundle\Controller;

use FOS\UserBundle\Controller\ProfileController as BaseController;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends BaseController
{

    /**
     * Override, add flash notification on update (for toast)
     */
    public function editAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->container->get('fos_user.profile.form');
        $formHandler = $this->container->get('fos_user.profile.form.handler');

        $process = $formHandler->process($user);
        if ($process) {
            $this->setFlash('success', 'Sus datos de usuario se han guardado');

            return new RedirectResponse($this->container->get('router')->generate('fos_user_profile_edit'));
        }

        $data = $form->getData();
        return $this->container->get('templating')->renderResponse(
                        'FOSUserBundle:Profile:edit.html.' . $this->container->getParameter('fos_user.template.engine'), array('form' => $form->createView(), 'theme' => $this->container->getParameter('fos_user.template.theme'), 'selectedcity' => $data->user->getCity())
        );
    }

    /**
     *  @Route("/profile/account", name="profile_account")
     */
    public function accountAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !($user instanceof UserInterface)) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        return $this->container->get('templating')->renderResponse(
            'DodiciFansworldWebBundle:User:profile_edit/account.html.twig', array('user' => $user)
        );
    }

}
