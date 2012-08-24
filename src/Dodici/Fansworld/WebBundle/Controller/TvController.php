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
use Dodici\Fansworld\WebBundle\Services\VideoAudienceManager;

/**
 * Tv controller.
 * @Route("/tv")
 */
class TvController extends SiteController {

    const LIMIT_VIDEOS = 6;
    const LIMIT_SEARCH_VIDEOS = 10;

    /**
     * @Route("", name="teve_home")
     * @Template
     */
    public function homeTabAction() {
        $user = $this->getUser();

        $videoRepo = $this->getRepository('Video');
        $homeVideoRepo = $this->getRepository('HomeVideo');

        $videosDestacadosFW = $videoRepo->search(null, null, self::LIMIT_VIDEOS, null, null, null, null, null, null);

        $videoDestacadoMain = $videoRepo->search(null, null, 1, null, null, null, null, null, null);

        $tags = $this->get('tagger')->usedInVideos('popular');


        $videoCategories = $this->getRepository('VideoCategory')->findBy(array());

        $channels = array();

        foreach ($videoCategories as $key => $videoCategory) {
            $channels[$key] = array(
                //'video' => $videoCategory->getVideos(),
                'video' => $videoRepo->search(null, null, 1, null, $videoCategory, null, null, null, null),
                'channelName' => $videoCategory->getTitle(),
                'slug' => $videoCategory->getSlug(),
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
     * @Route("/{id}/{slug}", name="video_show", requirements = {"id"="\d+"})
     * @Template()
     */
    public function videoDetailAction($id, $slug) {
        $video = $this->getRepository('Video')->find($id);
        $user = $this->getUser();
        $videosRelated = $this->getRepository('Video')->related($video, $user, self::LIMIT_VIDEOS);
        $videosRecommended = $this->getRepository('Video')->recommended($user, $video, self::LIMIT_VIDEOS);

        $sorts = array(
            'id' => 'toggle-video-types',
            'class' => 'sort-videos',
            'list' => array(
                array(
                    'name' => 'Relacionados',
                    'dataType' => 0,
                    'class' => 'active',
                ),
                array(
                    'name' => 'Más del usuario',
                    'dataType' => 1,
                    'class' => '',
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
     * @Route("/ajax/tv/get_audience", name="teve_getaudience")
     */
    public function getVideoAudience() {
        $manager = $this->get('video.audience');

        $request = $this->getRequest();
        $videoId = $request->get('video', false);
        $video = $this->getRepository('Video')->find($videoId);
        $user = $this->getUser();
        $manager->join($video, $user);
        $videoAudience = $this->getRepository('VideoAudience')->watching($video);

        $response = array();

        foreach ($videoAudience as $viewer) {
            array_push($response, array(
                'id' => $viewer->getId(),
                'username' => $viewer->getUsername(),
                'wall' => $this->generateUrl('user_wall', array('username' => $viewer->getUsername())),
                'image' => $this->getImageUrl($viewer->getImage(), 'micro_square')
            ));
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/ajax/tv/keepalive", name="teve_keepalive")
     */
    public function keepAlive() {
        $manager = $this->get('video.audience');
        
        $request = $this->getRequest();
        $videoId = $request->get('video', false);
        $video = $this->getRepository('Video')->find($videoId);
        $user = $this->getUser();
        
        $alive = $manager->keepalive($video, $user);
        
        return $this->jsonResponse($alive);
    }

    /**
     * @Route("/ajax/sort/detail", name="teve_ajaxsortdetail")
     */
    public function videoDetailSort() {
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

        foreach ($videos as $video) {
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

    /**
     * @Route("/tag/{term}", name="teve_taggedvideos")
     * @Template
     */
    public function taggedVideosAction($term) {
        $user = $this->getUser();
        $videoRepo = $this->getRepository('Video');

        $videos = $videoRepo->search($term, null, self::LIMIT_SEARCH_VIDEOS, null, null, null, null, null, null);

        return array(
            'user' => $user,
            'videos' => $videos,
            'term' => $term,
        );
    }

    /**
     * @Route("/team/{term}", name="teve_teamvideos")
     * @Template
     */
    public function teamVideosAction($term) {
        $user = $this->getUser();
        $videoRepo = $this->getRepository('Video');
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $term, 'active' => true));

        $videos = $videoRepo->search(null, null, self::LIMIT_SEARCH_VIDEOS, null, null, null, $team, null, null);

        return array(
            'user' => $user,
            'videos' => $videos,
            'term' => $term,
        );
    }

    /**
     * @Route("/idol/{term}", name="teve_idolvideos")
     * @Template
     */
    public function idolVideosAction($term) {
        $user = $this->getUser();
        $videoRepo = $this->getRepository('Video');
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $term, 'active' => true));

        $videos = $videoRepo->search(null, null, self::LIMIT_SEARCH_VIDEOS, null, null, null, $idol, null, null);

        return array(
            'user' => $user,
            'videos' => $videos,
            'term' => $term,
        );
    }

    /**
     * @Route("/explore/{slug}", name="teve_explorechannel")
     * @Template
     */
    public function exploreChannelAction($slug) {
        $user = $this->getUser();
        $videoRepo = $this->getRepository('Video');
        $activeCategory = $this->getRepository('VideoCategory')->findOneBySlug(array($slug));

        if (!$activeCategory) {
            throw new HttpException(404, "No existe el canal $slug");
        }

        $videos = $videoRepo->search(null, null, self::LIMIT_SEARCH_VIDEOS, null, $activeCategory, null, null, null, null);
        $videoCategories = $this->getRepository('VideoCategory')->findBy(array());


        return array(
            'user' => $user,
            'videos' => $videos,
            'channels' => $videoCategories,
            'activeChannel' => $activeCategory,
        );
    }

}
