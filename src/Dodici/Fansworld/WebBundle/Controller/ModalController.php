<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;

/**
 * Modal controller.
 * @Route("/modal")
 */
class ModalController extends SiteController
{

    /**
     * show media template
     * @Route("/{type}/show/{id}", name = "modal_media", requirements = { "type" = "photo|video", "id" = "\d+" } )
     * @Template()
     */
    public function mediaAction($type, $id)
    {
        if ($type == 'photo') {
            $repo = $this->getRepository('Photo');
        } else {
            $repo = $this->getRepository('Video');
        }
        
        $media = $repo->find($id);
        
        return array(
            'type' => $type,
            'media' => $media
        );
    }

}