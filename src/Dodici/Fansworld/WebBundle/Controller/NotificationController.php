<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Form\FormError;

use Symfony\Component\Validator\Constraints\Collection;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Entity\Notification;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Notification controller.
 * @Route("/notification")
 */
class NotificationController extends SiteController
{
	/**
     * @Route("/preferences", name="notification_preferences")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function preferencesAction()
    {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $preferences = $user->getNotifyprefs();
        $preflist = Notification::getTypeList();
        
        $defaultData = array('prefs' => $preferences);
        
        $collectionConstraint = new Collection(array(
                    'prefs' => array(new \Symfony\Component\Validator\Constraints\Choice(array('choices' => array_keys($preflist), 'multiple' => true))),
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('prefs', 'choice', array('required' => false, 'choices' => $preflist, 'label' => 'Notificar por mail', 'multiple' => true, 'expanded' => true))
                ->getForm();


        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $user->setNotifyprefs($data['prefs']);
                    $em->persist($user);
                    $em->flush();
                    $this->get('session')->setFlash('success', '¡Has cambiado tus preferencias con éxito!');
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error guardando preferencias'));
            }
        }

        return array('form' => $form->createView());
    }
}
