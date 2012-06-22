<?php

namespace Dodici\Fansworld\WebBundle\Controller;

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

/**
 * Batch controller.
 * @Route("/batch")
 */
class BatchController extends SiteController
{

    /**
     * @Route("/eventfeeding", name= "admin_batch_eventfeeding")
     */
    public function eventFeedingAction()
    {
        $df = $this->get('feeder.event');
        $df->feed();
		$df->pending();
    }

	/**
     * @Route("/videoprocessing", name= "admin_batch_videoprocessing")
     */
    public function videoProcessingAction()
    {
        $videos = $this->getRepository('Video')->pendingProcessing(10);
        $uploader = $this->get('video.uploader');
        
        foreach ($videos as $video) {
            $uploader->process($video);
        }
    }
    
}
