<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Request;

/**
 * Comment controller.
 * @Route("/template")
 */
class TemplateController extends SiteController
{

    /**
     * Get Comment Template
     * @Route("/ajax/get/comment", name="template_comment")
     * @Template
     */
    public function commentAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }

    /**
     * Get Video Template
     * @Route("/ajax/get/video", name="template_video")
     * @Template
     */
    public function videoAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }

    /**
     * Get Photo Template
     * @Route("/ajax/get/photo", name="template_photo")
     * @Template
     */
    public function photoAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }

    /**
     * Get General Template
     * @Route("/ajax/get/general", name="template_general")
     * @Template
     */
    public function generalAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }

    /**
     * Get Event Template
     * @Route("/ajax/get/event", name="template_event")
     * @Template
     */
    public function eventAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }

    /**
     * Get Team Template
     * @Route("/ajax/get/team", name="template_team")
     * @Template
     */
    public function teamAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }

    /**
     * Get Idol Template
     * @Route("/ajax/get/idol", name="template_idol")
     * @Template
     */
    public function idolAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }

    /**
     * Get Search template
     * @Route("/ajax/get/search", name="template_search")
     * @Template
     */
    public function searchAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }
    
    /**
     * Get Fans template
     * @Route("/ajax/get/fans", name="template_fans")
     * @Template
     */
    public function fansAction()
    {
        $request = $this->getRequest();
        $type = $request->get('type');

        return array(
            'typename' => $type
        );
    }
    
}
