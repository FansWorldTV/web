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

    /**
     * @Route("/i/{slug}", name="idol_wall")
     * @Template
     */
    public function wallAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBySlug($slug);
        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }else
            $this->get('visitator')->visit($idol);

        $hasComments = $this->getRepository('Comment')->countBy(array('idol' => $idol->getId()));
        $hasComments = $hasComments > 0 ? true : false;

        return array('idol' => $idol, 'hasComments' => $hasComments, 'isHome' => true);
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
        
        if(!$idol){
            throw new HttpException(404, "No existe el ídolo");
        }else{
            $this->get('visitator')->visit($idol);
        }
        
        $user = $this->getUser();
        $videoRepo = $this->getRepository('Video');
        $videoRepo instanceof VideoRepository;
        
        $videos = $videoRepo->search(null, $user, self::LIMIT_SEARCH, null, null, null, null, null, null, 'default', $idol);
        $countAll = $videoRepo->countSearch(null, $user, null, null, null, null, null, $idol);
        
        $addMore = $countAll > self::LIMIT_SEARCH ? true : false;
        
        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'user' => $user,
            'idol' => $idol
        );
    }
    
    /**
     *  @Route("/i/{slug}/biography", name="idol_biography")
     *  @Template()
     */
    public function biographyTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));
        
        if(!$idol){
            throw new HttpException(404, "No existe el ídolo");
        }else{
            $this->get('visitator')->visit($idol);
        }

        $user = $this->getUser();
        
        return array(
            'user' => $user,
            'idol' => $idol
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
            'containerClass' => 'fan-container'
        );
        $fans['list'] = $this->getRepository('User')->byIdols($idol);
    
        $return = array(
                'fans' => $fans,
                'idol' => $idol
        );
    
        return $return;
    }
}
