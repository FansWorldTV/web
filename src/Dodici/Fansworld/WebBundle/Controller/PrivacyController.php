<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;
use Dodici\Fansworld\WebBundle\Entity\Privacy;

class PrivacyController extends SiteController
{

    /**
     *  @Route("/edit/privacy", name="profile_privacy") 
     */
    public function privacyAction()
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof User) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        
        $privacyFields = array();
        $merge = array_merge(Privacy::getDefaultFieldPrivacy(),$user->getPrivacy());
        $possiblekeys = Privacy::getFields();
        foreach ($merge as $k => $v) {
            if (in_array($k, $possiblekeys)) $privacyFields[$k] = $v;
        }
        
        return $this->get('templating')->renderResponse(
                        'DodiciFansworldWebBundle:User:profile_edit/privacy.html.twig', array('user' => $user, 'privacyFields' => $privacyFields)
        );
    }

    /**
     * @Route("/ajax/edit/privacy",  name="profile_ajaxprivacy") 
     */
    public function ajaxPrivacyAction()
    {
        $request = $this->getRequest();
        $privacies = $request->get('privacy', false);
        $error = false;
        
        try {
            $user = $this->getUser();
            $user instanceof User;
            $user->setPrivacy($privacies);
            
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($user);
            $em->flush();
        } catch (\Exception $exc) {
            $error = $exc->getMessage();
        }
        
        return $this->jsonResponse(array('error' => $error));
    }

}
