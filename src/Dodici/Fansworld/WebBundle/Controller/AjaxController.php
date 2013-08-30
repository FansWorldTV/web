<?php

namespace Dodici\Fansworld\WebBundle\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Application\Sonata\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Serializer\Serializer;

/**
 * Ajax Controller
 */
class AjaxController extends SiteController
{
    const FW_VIDEO_LIST_LIMIT = 30;
    /**
     * Ajax list videos from fansworld
     * @Route("ajax/list/fansworld", name="home_ajaxfwlist")
     */
    public function ajaxFwVideoListAction()
    {
        $request = $this->getRequest();
        $serializer = $this->get('serializer');
        $page = $request->get('page', 1);
        $offset = ($page - 1) * self::FW_VIDEO_LIST_LIMIT;
        $fwVideos = $this->getRepository('Video')->findBy(array('highlight' => true, 'active' => true, 'author' => 1), array('createdAt' => 'desc'), self::FW_VIDEO_LIST_LIMIT, $offset);
        $countVideos = $this->getRepository('Video')->countBy(array('highlight' => true, 'active' => true, 'author' => 1));
        $addMore = $countVideos > ($page * self::FW_VIDEO_LIST_LIMIT) ? true : false;
        return $this->jsonResponse(array(
            'videos' => $serializer->values($fwVideos, 'home_video'),
            'addMore' => $addMore
        ));
    }
}