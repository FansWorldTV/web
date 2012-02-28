<?php

namespace Application\Sonata\UserBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseController;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use FOS\UserBundle\Model\UserInterface;

class ResettingController extends BaseController
{
	/**
	 * Override to redirect to the homepage instead
	 */	
    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('homepage');
    }
}
