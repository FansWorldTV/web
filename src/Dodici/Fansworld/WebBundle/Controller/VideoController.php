<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;

/**
 * Video controller.
 * @Route("/video")
 */
class VideoController extends SiteController
{

    /**
     * @Route("/{id}/{slug}", name= "video_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template
     */
    public function showAction($id)
    {
        // TODO: video show action, show video + comments, allow commenting + answering root comments

        $video = $this->getRepository('Video')->findOneBy(array('id' => $id, 'active' => true));
        
        $this->securityCheck($video);

        return array('video' => $video);
    }

    /**
     * video list
     * 
     * @Route("/list", name="video_list")
     * @Template
     */
    public function listAction()
    {
        // TODO: everything

        $videos = $this->getRepository("Video")->findBy(array("active" => true), array("createdAt" => "DESC"));

        return array(
            'videos' => $videos
        );
    }
    
	/**
     * video category page
     * 
     * @Route("/category/{id}/{slug}", name="video_category", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template
     */
    public function categoryAction($id)
    {
       // TODO: everything
	   $category = $this->getRepository('VideoCategory')->find($id);
	   if (!$category) throw new HttpException(404, 'Categoría no encontrada');
       $videos = $this->getRepository("Video")->findBy(array("active" => true, "videocategory" => $category->getId()), array("createdAt" => "DESC"));
       
       return array(
            'videos' => $videos
            );
    }

    /**
     * my videos
     * 
     * @Route("/my-videos", name="video_myvideos") 
     * @Template
     */
    public function myVideosAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $videos = $this->getRepository('Video')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'));
        
        return array(
            'videos' => $videos
        );
    }

}
