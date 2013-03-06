<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Dodici\Fansworld\WebBundle\Model\UserRepository;

/**
 * Playlist controller.
 * @Route("/playlist")
 */
class VideoPlaylistController extends SiteController
{

    /**
     * @Route("/add/ajax", name="playlist_ajaxadd")
     * @Secure(roles="ROLE_USER")
     */
    public function ajaxAddAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $response = 'add';
        $videoid = $request->get('video_id');

        if (!$videoid) throw new HttpException(400, 'Invalid video_id');
        $video = $this->getRepository('Video')->find($videoid);
        if (!$video) throw new HttpException(404, 'Video not found');

        $this->get('video.playlist')->add($video, $user);

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/remove/ajax", name="playlist_ajaxremove")
     * @Secure(roles="ROLE_USER")
     */
    public function ajaxRemoveAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $response = 'remove';
        $videoid = $request->get('video_id');

        if (!$videoid) throw new HttpException(400, 'Invalid video_id');
        $video = $this->getRepository('Video')->find($videoid);
        if (!$video) throw new HttpException(404, 'Video not found');

        $this->get('video.playlist')->remove($video, $user);

        return $this->jsonResponse($response);
    }

    /**
     * List playlist (watch leter)
     * @Route("/list", name="playlist_list")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function listAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $pagination = $this->pagination(array('createdAt'), 'createdAt', 'ASC');

        $wls = $this->get('video.playlist')->get(
            $user,
            $pagination['limit'],
            $pagination['offset'],
            array($pagination['sort'] => $pagination['sort_order'])
        );

        $allowedfields = array('author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount');
        $extrafields = $this->getExtraFields($allowedfields);

        $return = array();
        foreach ($wls as $wl) $return[] = $this->videoValues($wl->getVideo(), $extrafields);

        return array(
            'user' => $user,
            'videos' => $return
        );
    }
}
