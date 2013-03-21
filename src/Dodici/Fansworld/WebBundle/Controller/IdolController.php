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
use Dodici\Fansworld\WebBundle\Entity\Team;
use Dodici\Fansworld\WebBundle\Entity\IdolCareer;
use Application\Sonata\UserBundle\Entity\Notification;

/**
 * Idol controller
 * @Route("/idol")
 */
class IdolController extends SiteController
{

    const LIMIT_SEARCH = 20;
    const LIMIT_NOTIFICATIONS = 5;
    const LIMIT_PHOTOS = 8;
    const LIMIT_LIST_IDOL = 10;

    /**
     * @Route("", name="idol_home")
     * @Template
     */
    public function homeAction()
    {
        $videosHighlighted = $this->getRepository('Video')->search(null, null, 4, null, null, true, null, null, null, 'likes');
        $topIdols = $this->getRepository('Idol')->findBy(array('active' => true), array('fanCount' => 'desc'), 3);
        $listIdols = $this->getRepository('Idol')->findBy(array('active' => true), array('fanCount' => 'desc'));
        $categories = $this->getRepository('TeamCategory')->findBy(array(), array('title' => 'desc'));

        $count = $this->getRepository('Idol')->countBy(array());
        $gotMore = $count > self::LIMIT_LIST_IDOL ? true : false;

        return array(
            'videosHighlighted' => $videosHighlighted,
            'topIdols' => $topIdols,
            'listIdols' => $listIdols,
            'categories' => $categories,
            'gotMore' => $gotMore
        );
    }

    /**
     * @Route("/ajax/list", name="idol_ajaxlist")
     */
    public function ajaxListAction()
    {
        $request = $this->getRequest();
        $page = $request->get('page', 1);
        $tcId = $request->get('tc', false);
        $offset = ($page - 1 ) * self::LIMIT_LIST_IDOL;
        $response = array(
            'gotMore' => false,
            'idols' => array()
        );

        $idolsRepo = $this->getRepository('Idol');
        $idolsRepo instanceof Idol;

        $tc = $this->getRepository('TeamCategory')->find($tcId);

        if (!$tc) {
            $idols = $idolsRepo->findBy(array(), array('fanCount' => 'desc'), self::LIMIT_LIST_IDOL, $offset);
            $count = $idolsRepo->countBy(array());
        } else {
            $idols = $idolsRepo->byTeamCategory($tc, self::LIMIT_LIST_IDOL, $offset);
            $count = $idolsRepo->countByTeamCategory($tc);
        }

        $response['gotMore'] = ($count / $page) > self::LIMIT_LIST_IDOL ? true : false;

        foreach ($idols as $idol) {

            if($this->getUser() instanceof User){
                $idolship = $this->getRepository('Idolship')->findBy(array('author' => $this->getUser()->getId(), 'idol' => $idol->getId()));
            }else{
                $idolship = false;
            }

            $rankedFans = $this->getRepository('Idolship')->rankedUsersScore($idol, 1);

            $topFans = array();

            foreach ($rankedFans as $iship) {
                $topFans = array(
                    'name' => (string) $iship->getAuthor(),
                    'url' => $this->generateUrl('user_land', array('username' => $iship->getAuthor()->getUsername()))
                );
            }

            $idolUrl = $this->generateUrl('idol_land', array('slug' => $idol->getSlug()));

            $idolCareer = $idol->getTeamName();
            
            if($idolCareer instanceof IdolCareer){
                if ($idolCareer->getTeam() instanceof Team) {
                    $teamUrl = $this->generateUrl('team_land', array('slug' => $idolCareer->getTeam()->getSlug()));
                } else {
                    $teamUrl = "";
                }
            }
            
            $typesUrl = array(
                'photos' => null,
                'videos' => null,
                'fans' => null
            );
                    
            $router = $this->get('router');
            foreach($typesUrl as $key=>$value){
                $typesUrl[$key] = $router->generate('idol_' . $key, array('slug'=> $idol->getSlug()));
            }

            $response['idols'][] = array(
                'id' => $idol->getId(),
                'slug' => $idol->getSlug(),
                'name' => (string) $idol,
                'avatar' => $this->getImageUrl($idol->getImage(), 'small'),
                'idolUrl' => $idolUrl,
                'teamUrl' => $teamUrl,
                'team' => $idolCareer instanceof IdolCareer ? (string) $idolCareer->getTeam() : '',
                'fanCount' => $idol->getFanCount(),
                'videoCount' => $idol->getVideoCount(),
                'photoCount' => $idol->getPhotoCount(),
                'isFan' => $idolship ? true : false,
                'topFan' => isset($topFans['name']) ? $topFans['name'] : false,
                'topFanUrl' => isset($topFans['url']) ? $topFans['url'] : false,
                'photosUrl' => $typesUrl['photos'],
                'videosUrl' => $typesUrl['videos'],
                'fansUrl' => $typesUrl['fans']
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/{slug}/wall", name="idol_wall")
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
            'highlights' => $highlights,
        );
    }

    /**
     * @Route("/{slug}/twitter", name= "idol_twitter")
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
     * @Route("/{slug}/photos", name="idol_photos")
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
     *  @Route("/{slug}", name="idol_land")
     *  @Route("/{slug}/videos", name="idol_videos")
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

        $videos = $videoRepo->search(null, $user, self::LIMIT_SEARCH, null, null, null, null, null, null, 'default', $idol);
        $countAll = $videoRepo->countSearch(null, $user, null, null, null, null, null, $idol);

        $addMore = $countAll > self::LIMIT_SEARCH ? true : false;

        $sorts = array(
            'id' => 'toggle-video-types',
            'class' => 'list-videos',
            'list' => array(
                array(
                    'name' => 'Destacados',
                    'dataType' => 0,
                    'class' => '',
                ),
                array(
                    'name' => 'Más vistos',
                    'dataType' => 1,
                    'class' => '',
                ),
                array(
                    'name' => 'Más vistos del día',
                    'dataType' => 3,
                    'class' => '',
                ),
                array(
                    'name' => 'Populares',
                    'dataType' => 2,
                    'class' => 'active',
                )
            )
        );

        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'user' => $user,
            'idol' => $idol,
            'sorts' => $sorts,
        	'isHome' => true
        );
    }

    /**
     *  @Route("/{slug}/biography", name="idol_biography")
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
     * @Route("/{slug}/fans", name="idol_fans")
     * @Template
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
            'list' => $this->getRepository('User')->byIdols($idol, null, 'score'),
        );


        $return = array(
            'fans' => $fans,
            'idol' => $idol
        );

        return $return;
    }

    /**
     * @Route("/{slug}/eventos", name="idol_eventos")
     * @Template
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
