<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
use Application\Sonata\UserBundle\Entity\Notification;

/**
 * Tv controller.
 * @Route("/tv")
 */
class TvController extends SiteController
{

    const LIMIT_VIDEOS = 6;

    /**
     * @Route("", name="teve_home")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function homeTabAction()
    {
        $user = $this->getUser();
        
        $videoRepo = $this->getRepository('Video');
        $homeVideoRepo = $this->getRepository('HomeVideo');
        
        $videosDestacadosFW = $videoRepo->search(null, null, self::LIMIT_VIDEOS, null, null, null, null, null,null);
        
        $videoDestacadoMain = $videoRepo->search(null, null, 1, null, null, null, null, null,null);
        $tags = array('tag1','otro tag diferente', 'shortag', 'dancing');
        
        
        $videoCategoryRepo = $this->getRepository('VideoCategory');
        $videoCategorys = $videoCategoryRepo->findBy(array());
        
        $channels = array();
        
        foreach ($videoCategorys as $key => $videoCategory){
            $channels[$key] = array(
                //'video' => $videoCategory->getVideos(),
                'video' => $videoRepo->search(null, null, 1, null, $videoCategory, null, null, null,null),
                'channelName' => $videoCategory->getTitle(),
            );
        }
        
        return array(
            'user' => $user, 
            'channels' => $channels,
            'videoDestacadoMain' => $videoDestacadoMain,
            'videosDestacadosFW' => $videosDestacadosFW,
            'tags' => $tags, 
       );
       
    }
    
    /**
     * @Route("/{id}/{slug}", name="teve_videodetail", requirements = {"id"="\d+"})
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function videoDetailAction($id, $slug)
    {
        $video = $this->getRepository('Video')->find($id);
        $user = $this->getUser();
        $videosRelated = $this->getRepository('Video')->related($video, $user, self::LIMIT_VIDEOS);
        $videosRecommended = $this->getRepository('Video')->recommended($user, $video, self::LIMIT_VIDEOS);
        
        $sorts = array(
            'id'   => 'toggle-video-types',
            'class'=> 'sort-videos',
            'list' => array(
                array(
                    'name'     => 'Relacionados', 
                    'dataType' => 0, 
                    'class'    => 'active',
                ),
                array(
                    'name'     => 'Más del usuario',
                    'dataType' => 1,
                    'class'    => '',
                )
            )
        );
        
        return array(
            'video' => $video,
            'user' => $user,
            'videosRelated' => $videosRelated,
            'videosRecommended' => $videosRecommended,
            'sorts' => $sorts
        );
    }
    
    /**
     * @Route("/ajax/sort/detail", name="teve_ajaxsortdetail")
     */
    public function videoDetailSort(){
      $request = $this->getRequest();
      $videoId = $request->get('video', false);
      $sortType = $request->get('sort', 0);
      $viewer = $this->getUser();
      
      $videoRelated = $this->getRepository('Video')->find($videoId);
      
      $response = array('videos' => array());
      
      switch ($sortType) {
        case 0:
          $videos = $this->getRepository('Video')->related($videoRelated, $viewer, self::LIMIT_VIDEOS);
          break;
        case 1:
          $videos = $this->getRepository('Video')->moreFromUser($videoRelated->getAuthor(), $videoRelated, $viewer, self::LIMIT_VIDEOS);
      }
      
      foreach($videos as $video){
        $response['videos'][] = array(
          'id' => $video->getId(),
          'slug' => $video->getSlug(),
          'title' => $video->getTitle(),
          'content' => substr($video->getContent(), 0, 52) . "...",
          'image' => $this->getImageUrl($video->getImage(), 'medium')
        );
      }
      
      return $this->jsonResponse($response);
    }
}
