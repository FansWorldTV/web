<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Sonata\UserBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;

use FOS\UserBundle\Form\Handler\RegistrationFormHandler as BaseHandler;

class RegistrationFormHandler extends BaseHandler
{
    
    public function process($confirmation = false)
    {
        $user = $this->userManager->createUser();
        \Doctrine\Common\Util\Debug::dump($this->form->getErrors());
        \Doctrine\Common\Util\Debug::dump($this->form->getData());
        
        $user->setUsername('abc'.uniqid());
        
        $this->form->setData($user);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid()) {
                // generate username
                $username = $user->getFirstname().'.'.$user->getLastname();
                $exists = $this->userManager->findUserByUsername($username);
                if ($exists) $username .= '.' . uniqid();
                $user->setUsername($username);
                
                $this->onSuccess($user, $confirmation);

                return true;
            }
        }

        return false;
    }

}
