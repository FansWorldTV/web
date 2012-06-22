<?php

namespace Application\Sonata\UserBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use Dodici\Fansworld\WebBundle\Controller\SiteController;

class ValidateProfileController extends SiteController
{

    /**
     * @Route("/ajax/validate-profile", name="profile_validate")
     */
    public function validateAction()
    {
        $request = $this->getRequest();
        $username = $request->get('username', false);
        $email = $request->get('email', false);
        $user = $this->get('security.context')->getToken()->getUser();

        if (!($user instanceof User))
            $user = null; // throw new \Exception('Acceso denegado');

        $isValidEmail = false;

        if ($email) {
            $findByMail = $this->getRepository('User')->findOneByEmail($email);

            if ((is_null($user) && !$findByMail) || (!is_null($user) && ($findByMail == $user || !$findByMail))) {
                $isValidEmail = filter_var($email, FILTER_VALIDATE_EMAIL) ? true : false;
            }
        }

        $isValidUsername = false;

        if ($username) {
            if (preg_match('/^[a-zA-Z0-9.\-]+$/', $username) > 0) {
                if (strlen($username) > 3 && strlen($username < 30)) {
                    $findByUser = $this->getRepository('User')->findOneByUsername($username);
                    if ((is_null($user) && !$findByUser) || (!is_null($user) && ($findByUser == $user || !$findByUser))) {
                        $isValidUsername = true;
                    }
                }
            }
        }

        return $this->jsonResponse(array(
                    'isValidEmail' => $isValidEmail,
                    'isValidUsername' => $isValidUsername
                ));
    }

}