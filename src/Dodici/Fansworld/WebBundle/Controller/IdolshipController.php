<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Idolship;

/**
 *  Idolship controller
 *  @Route("/idolship")
 */
class IdolshipController extends SiteController {

    /**
     * Toggle idolship
     * 
     *  @Route("/ajax/toggle/", name="idolship_ajaxtoggle")
     */
    public function ajaxToggleAction() {
        try {
            $request = $this->getRequest();
            $ididol = intval($request->get('idol-id'));
            $user = $this->getUser();

            if (!$user instanceof User)
                throw new \Exception('Debe iniciar sesión');

            $idol = $this->getRepository('Idol')->find($ididol);
            if (!$idol)
                throw new \Exception('Idolo no encontrado');

            $translator = $this->get('translator');
            $appstate = $this->get('appstate');

            $idolship = $appstate->idolshipWith($idol);
            $em = $this->getDoctrine()->getEntityManager();
            if ($idolship) {
                $em->remove($idolship);
                $em->flush();

                $message = $translator->trans('You are no longer a fan of') . ' "' . (string) $idol . '"';
                $buttontext = $translator->trans('add_idol');
                $isFan = false;
            } else {
                $idolship = new Idolship();
                $idolship->setAuthor($user);
                $idolship->setIdol($idol);
                $em->persist($idolship);
                $em->flush();

                $message = $translator->trans('You are now a fan of') . ' "' . (string) $idol . '"';
                $buttontext = $translator->trans('remove_idol');
                $isFan = true;
            }

            $response = new Response(json_encode(array(
                                'buttontext' => $buttontext,
                                'message' => $message,
                                'isFan' => $isFan
                            )));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

}
