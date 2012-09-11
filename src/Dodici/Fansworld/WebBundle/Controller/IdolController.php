<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Idol;
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

class IdolController extends SiteController
{

    const LIMIT_SEARCH = 20;
    const LIMIT_NOTIFICATIONS = 5;
    const LIMIT_PHOTOS = 8;
    const LIMIT_LIST_IDOL = 10;

    /**
     * @Route("/i", name="idol_home")
     * @Template
     */
    public function homeAction()
    {
        $videosHighlighted = $this->getRepository('Video')->search(null, null, 4, null, null, true, null, null, null, 'likes');
        $topIdols = $this->getRepository('Idol')->findBy(array('active' => true), array('fanCount' => 'desc'), 3);
        $listIdols = $this->getRepository('Idol')->findBy(array('active' => true), array('fanCount' => 'desc'));

        return array(
            'videosHighlighted' => $videosHighlighted,
            'topIdols' => $topIdols,
            'listIdols' => $listIdols
        );
    }

    /**
     * @Route("/i/ajax/list", name="idol_ajaxlist")
     */
    public function ajaxListAction()
    {
        $request = $this->getRequest();
        $page = $request->get('page', 1);
        $offset = ($page - 1 ) * self::LIMIT_LIST_IDOL;
        $response = array();
        
        return $this->jsonResponse($response);
    }

    /**
     * @Route("/i/{slug}", name="idol_wall")
     * @Template
     */
    public function wallTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBySlug($slug);
        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }else
            $this->get('visitator')->visit($idol);

        $highlights = $this->getRepository('video')->highlights($idol, 4);

        return array(
            'idol' => $idol,
            'isHome' => true,
            'highlights' => $highlights,
        );
    }

    /**
     * @Route("/i/{slug}/twitter", name= "idol_twitter")
     * @Template()
     */
    public function twitterTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));

        if (!$idol)
            throw new HttpException(404, 'No existe el ídolo');
        else {
            $ttScreenName = $idol->getTwitter();
            if (!$ttScreenName)
                throw new HttpException(404, 'Idolo sin twitter');
            $this->get('visitator')->visit($idol);
        }

        return array('idol' => $idol);
    }

    /**
     * @Route("/i/{slug}/photos", name="idol_photos")
     * @Template
     */
    public function photosTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));

        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }else
            $this->get('visitator')->visit($idol);

        $photos = $this->getRepository('Photo')->searchByEntity($idol, self::LIMIT_PHOTOS);
        $photosTotalCount = $this->getRepository('Photo')->countByEntity($idol);

        $viewMorePhotos = $photosTotalCount > self::LIMIT_PHOTOS ? true : false;

        return array(
            'idol' => $idol,
            'photos' => $photos,
            'gotMore' => $viewMorePhotos
        );
    }

    /**
     * Idol videos
     * 
     *  @Route("/i/{slug}/videos", name="idol_videos")
     *  @Template()
     */
    public function videosTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));

        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        } else {
            $this->get('visitator')->visit($idol);
        }

        $user = $this->getUser();
        $videoRepo = $this->getRepository('Video');
        $videoRepo instanceof VideoRepository;

        $videos = $videoRepo->search(null, $user, self::LIMIT_SEARCH, null, null, null, null, null, null, 'default', $idol);
        $countAll = $videoRepo->countSearch(null, $user, null, null, null, null, null, $idol);

        $addMore = $countAll > self::LIMIT_SEARCH ? true : false;

        $sorts = array(
            'id' => 'toggle-video-types',
            'class' => 'list-videos',
            'list' => array(
                array(
                    'name' => 'destacados',
                    'dataType' => 0,
                    'class' => '',
                ),
                array(
                    'name' => 'masVistos',
                    'dataType' => 1,
                    'class' => '',
                ),
                array(
                    'name' => 'populares',
                    'dataType' => 2,
                    'class' => 'active',
                ),
                array(
                    'name' => 'masVistosDia',
                    'dataType' => 3,
                    'class' => '',
                ),
            )
        );

        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'user' => $user,
            'idol' => $idol,
            'sorts' => $sorts
        );
    }

    /**
     *  @Route("/i/{slug}/biography", name="idol_biography")
     *  @Template()
     */
    public function infoTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));

        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        } else {
            $this->get('visitator')->visit($idol);
        }

        $user = $this->getUser();

        $personalData = array(
            'firstname',
            'lastname',
            'nicknames',
            'birthday',
            'country',
            'origin',
            'sex',
            'idolcareers',
        );

        return array(
            'user' => $user,
            'idol' => $idol,
            'personalData' => $personalData,
        );
    }

    /**
     * @Route("/i/{slug}/fans", name="idol_fans")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function fansTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));
        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }else
            $this->get('visitator')->visit($idol);


        $fans = array(
            'ulClass' => 'fans',
            'containerClass' => 'fan-container',
            'list' => $this->getRepository('User')->byIdols($idol),
        );


        $return = array(
            'fans' => $fans,
            'idol' => $idol
        );

        return $return;
    }

    /**
     * @Route("/i/{slug}/eventos", name="idol_eventos")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function eventosTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));
        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }else
            $this->get('visitator')->visit($idol);

        $eventos = $this->getRepository('Event')->ByIdol($idol);

        $return = array(
            'eventos' => $eventos,
            'idol' => $idol,
        );

        return $return;
    }

}
