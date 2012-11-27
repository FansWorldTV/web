<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Services\ContactImporter;

/**
 * @Route("/inviter") 
 */
class InviteController extends SiteController
{

    /**
     * @Route("/", name="invite_index") 
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/ajax/import/", name="invite_ajaximport")
     */
    public function ajaxImportAction()
    {
        $request = $this->getRequest();
        $mail = $request->get('mail', false);
        $password = $request->get('password', false);
        $provider = $request->get('provider', false);

        $response = array(
            'error' => true,
            'contacts' => null
        );

        if ($mail && $password && $provider) {
            $importer = $this->get('contact.importer');
            $importer instanceof ContactImporter;

            $response['contacts'] = $importer->import($mail, $password, $provider);
            $response['error'] = false;
        } else {
            $response['error'] = 'Mail o contraseña invalido';
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/ajax/generate-invitation/", name="invite_generateInvitation")
     */
    public function ajaxGenerateInvitation()
    {
        $request = $this->getRequest();
        $users2bInvited = $request->get('users', null);
        $response = null;

        if ($users2bInvited) {
            $user = $this->getUser();

            $importer = $this->get('contact.importer');
            $importer instanceof ContactImporter;


            foreach ($users2bInvited as $user2bInvited) {
                $inviteUrl = $importer->inviteUrl($user);

                $subject = "Invitation";
                $html = $this->get('templating')->render('DodiciFansworldWebBundle:Invite:new_invitation.html.twig', array('url' => $inviteUrl, 'who' => $user));
                
                $sent = $this->container->get('fansworldmailer')->send($user2bInvited,$subject,$html); 

                $response['invites'][$user2bInvited] = array(
                    'url' => $inviteUrl,
                    'sent' => $sent
                );
            }
        }

        return $this->jsonResponse($response);
    }

}
