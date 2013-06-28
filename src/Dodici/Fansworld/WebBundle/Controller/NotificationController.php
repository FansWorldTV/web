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
    const LIMIT_NOTIFICATIONS = 30;

    /**
     * Only for testing purpose
     * @Route("/test", name="notification_test")
     */
//    public function testAction(){
//      $notification = $this->getRepository('Notification')->findOneBy(array());
//      
//      $html = $this->render(
//                'DodiciFansworldWebBundle:Notification:Mail/'.$notification->getTypeName().'.html.twig', 
//                array(
//                    'notification' => $notification, 
//                    'targetUser' => $notification->getTarget()
//                )
//              ); 
//     
//      $this->get('fansworldmailer')->send('nrosso@fansworld.tv', '[BENCH] fw mail test', $html);
//      
//      return $html;
//    }


    /**
     * @Route("/testregister", name="notification_test_register")
     * @Secure(roles="ROLE_USER")
     */
//    public function testAction(){
//        $user = $this->getUser();        
//        $html = $this->render(
//                'DodiciFansworldWebBundle:Mail:welcome.html.twig', 
//                array('targetUser' => $user)
//              ); 
//     
//        $this->get('fansworldmailer')->send('ASDASDASD3SSS@outlook.com', '[BENCH] fw mail test', $html);
//      
//        return $html;
//    }    

    /**
     * @Route("/preferences", name="notification_preferences")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function preferencesAction()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();
        $notifyPreferences = $user->getNotifyprefs();
        $notifyMail = $user->getNotifymail();
        $preftypes = Notification::getTypeList();
        $prefList = array();
        $maillist = array();
       
        foreach ($preftypes as $key => $pt) {
            $prefList[$key] = $this->trans('notification_'.$pt['type']);
            $mailList[$key] = $this->trans('notification_'.$pt['type']);
            
        }

        $defaultData = array('prefs' => $notifyPreferences,
                             'mails' => $notifyMail);

        $collectionConstraint = new Collection(array(
                    'prefs' => array(new \Symfony\Component\Validator\Constraints\Choice(array('choices' => array_keys($prefList), 'multiple' => true))),
        			'mails' => array(new \Symfony\Component\Validator\Constraints\Choice(array('choices' => array_keys($mailList), 'multiple' => true))),
                ));

        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
                ->add('prefs', 'choice', array('required' => false, 'choices' => $prefList, 'label' => 'Notificar por mail', 'multiple' => true, 'expanded' => true))
                ->add('mails', 'choice', array('required' => false, 'choices' => $mailList, 'label' => 'Notificar por mail', 'multiple' => true, 'expanded' => true))
                ->getForm();

        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();
                $user->setNotifyprefs($data['prefs']);
                $user->setNotifymail($data['mails']);
                $em->persist($user);
                $em->flush();
                $this->get('session')->setFlash('success', '¡Has cambiado tus preferencias con éxito!');
            } catch (\Exception $e) {
                $form->addError(new FormError('Error guardando preferencias'));
            }
        }

        return array('form' => $form->createView(), 'preftypes' => $preftypes);
    }

    /**
     * @Route("/details", name="notification_details")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function detailsAction()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $notiRepo = $this->getRepository('Notification');
        $notifications = $notiRepo->findBy(array('target' => $user->getId(), 'readed' => false, 'active' => true), array('createdAt' => 'DESC'));
        $response = array();
        $dates = array();
        $readed = array();
        foreach ($notifications as $notification) {
            $response[] = $this->renderView('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $notification));
            $dates[] = $notification->getCreatedAt();
        }
        $lastVideos = $this->getRepository('Video')->findBy(array('highlight' => true), array('createdAt' => 'desc'), 4);
        return array('notifications' => $response, 'user' => $user,  'lastVideos' => $lastVideos, 'dates' => $dates);
    }


    /**
     * @Route("/all", name="notification_all")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function detailsAllAction()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $notiRepo = $this->getRepository('Notification');
        $notifications = $notiRepo->findBy(array('target' => $user->getId()), array('createdAt' => 'DESC'), self::LIMIT_NOTIFICATIONS);
        $response = array();
        $dates = array();
        foreach ($notifications as $notification) {
            if ($notification->getTypeName() != 'friendship_pending') {
                $response[] = $this->renderView('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $notification));
                $dates[] = $notification->getCreatedAt();
                $readed[] = $notification->getReaded();
            } else {
                if (false  == $notification->getReaded()) {
                    $response[] = $this->renderView('DodiciFansworldWebBundle:Notification:notification.html.twig', array('notification' => $notification));
                    $dates[] = $notification->getCreatedAt();
                    $readed[] = $notification->getReaded();
                }
            }
        }
        $lastVideos = $this->getRepository('Video')->findBy(array('highlight' => true), array('createdAt' => 'desc'), 4);
        return array('notifications' => $response, 'user' => $user,  'lastVideos' => $lastVideos, 'dates' => $dates, 'readed' => $readed);
    }
}
