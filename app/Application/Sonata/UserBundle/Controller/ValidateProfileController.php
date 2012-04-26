<?php

namespace Application\Sonata\UserBundle\Controller;

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

        $findByMail = $this->getRepository('User')->findBy(array('email' => $email), array());

        $isValidEmail = false;
        if ($findByMail->getId() == $user->getId() || !$findByMail) {
            $isValidEmail = filter_var($email, FILTER_VALIDATE_EMAIL) ? true : false;
        }

        $isValidUsername = false;

        if (preg_match("/^[a-zA-Z0-9.\-]+$/", $username) > 0) {
            if (strlen($username) > 3 && strlen($username < 30)) {
                $isValidUsername = true;
            }
        }

        return $this->jsonResponse(array(
                    'isValidEmail' => $isValidEmail,
                    'isValidUsername' => $isValidUsername
                ));
    }

}