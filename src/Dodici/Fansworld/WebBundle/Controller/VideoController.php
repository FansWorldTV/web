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

    const cantVideos = 10;

    /**
     * @Route("/{id}/{slug}", name= "video_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template
     */
    public function showAction($id)
    {
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
        $videos = $this->getRepository("Video")->findBy(array("active" => true), array("createdAt" => "DESC"), self::cantVideos);
        $countAll = $this->getRepository('Video')->countBy(array('active' => true));
        $addMore = $countAll > self::cantVideos ? true : false;

        return array(
            'videos' => $videos,
            'addMore' => $addMore
        );
    }

    /**
     * @Route("/ajax/search", name="video_ajaxsearch") 
     */
    public function ajaxSearchAction()
    {
        $request = $this->getRequest();

        $page = $request->get('page');
        $query = $request->get('query', false);

        $user = $this->get('security.context')->getToken()->getUser();

        $page = (int) $page;
        $offset = ($page - 1) * self::cantVideos;

        $response = array();

        if (!$query) {
            $videos = $this->getRepository('Video')->findBy(array('active' => true), array('createdAt' => 'DESC'), self::cantVideos, $offset);
            $countAll = $this->getRepository('Video')->countBy(array('active' => true));
        } else {
            $videos = $this->getRepository('Video')->searchText($query, $user, self::cantVideos, $offset);
            $countAll = $this->getRepository('Video')->countSearchText($query, $user);
        }

        $response['addMore'] = $countAll > (($page) * self::cantVideos) ? true : false;
        foreach ($videos as $video) {
            $tags = array();
            foreach ($video->getHastags() as $tag) {
                array_push($tags, $tag->getTag()->getTitle());
            }

            $response['videos'][] = array(
                'id' => $video->getId(),
                'title' => $video->getTitle(),
                'image' => $this->getImageUrl($video->getImage()),
                'author' => (string) $video->getAuthor(),
                'slug' => $video->getSlug(),
                'videoplayerurl' => $this->get('flumotiontwig')->getVideoPlayerUrl($video),
                'tags' => $tags
            );
        }

        return $this->jsonResponse($response);
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
        if (!$category)
            throw new HttpException(404, 'Categoría no encontrada');
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

    /**
     * Show by tag
     * 
     * @Route("/tag/{slug}", name = "video_tags")
     * @Template()
     */
    public function tagsAction($slug)
    {
        $response = array('videos' => false);
        if ($slug) {
            $user = $this->get('security.context')->getToken()->getUser();
            $tags = $this->getRepository('Tag')->findBy(array('title' => $slug));

            foreach ($tags as $tag) {
                $videos = $this->getRepository('Video')->byTag($tag, $user, self::cantVideos);
                foreach ($videos as $video) {
                    /**
                    $tags = array();
                    foreach ($video->getHastags() as $tag) {
                        array_push($tags, $tag->getTag()->getTitle());
                    }
                    $response['videos'][] = array(
                        'id' => $video->getId(),
                        'title' => $video->getTitle(),
                        'image' => $this->getImageUrl($video->getImage()),
                        'author' => (string) $video->getAuthor(),
                        'slug' => $video->getSlug(),
                        'videoplayerurl' => $this->get('flumotiontwig')->getVideoPlayerUrl($video),
                        'tags' => $tags
                    );
                     * 
                     */
                    $response['videos'][] = $video;
                }
            }
        }
        
        $countAll = $this->getRepository('Video')->countByTag($tag, $user);
        $response['addMore'] = $countAll > self::cantVideos ? true : false;

        return $response;
    }

}
