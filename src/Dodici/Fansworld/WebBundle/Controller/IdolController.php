<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Idol;
use Symfony\Component\Config\Definition\Exception\Exception;
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
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;

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
     * @Secure(roles="ROLE_USER")
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
     * @Route("/{id}/next", name="idol_next")
     */
    public function nextAction($id)
    {
        $idol = $this->getRepository('Idol')->find($id);
        $next = $this->getRepository('Idol')->next($idol);


        return $this->forward('DodiciFansworldWebBundle:Idol:videosTab', array('slug'=> $next->getSlug()));
    }

    /**
     * @Route("/{id}/previous", name="idol_previous")
     */
    public function previousAction($id)
    {
        $idol = $this->getRepository('Idol')->find($id);
        $previous = $this->getRepository('Idol')->previous($idol);

        return $this->forward('DodiciFansworldWebBundle:Idol:videosTab', array('slug'=> $previous->getSlug()));
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
        }
        else
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
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function photosTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));

        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }
        else
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
      * @Secure(roles="ROLE_USER")
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
     * @Secure(roles="ROLE_USER")
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
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function fansTabAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));
        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }
        else
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
        }
        else
            $this->get('visitator')->visit($idol);

        $eventos = $this->getRepository('Event')->ByIdol($idol);


        $return = array(
            'eventos' => $eventos,
            'idol' => $idol,
        );

        return $return;
    }

    /**
     * @Route("/change/image", name="idol_change_imageSave")
     * @Secure(roles="ROLE_ADMIN")
     * @Template
     */
    public function changeImageSaveAction()
    {
        $request = $this->getRequest();
        $idolId = $request->get('idol', null);
        $em = $this->getDoctrine()->getEntityManager();

        $tempFile = $request->get('tempFile');
        $originalFileName = $request->get('originalFile');
        $realWidth = $request->get('width');
        $realHeight = $request->get('height');
        $type = $request->get('type');

        $lastdot = strrpos($originalFileName, '.');
        $originalFile = substr($originalFileName, 0, $lastdot);
        $ext = substr($originalFileName, $lastdot);

        $finish = false;
        $form = $this->_createForm();

        if($idolId){
            $idol = $this->getRepository('Idol')->find($idolId);
        }else{
            throw new Exception('No idol');
        }

        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $mediaManager = $this->get("sonata.media.manager.media");

                    $cropOptions = array(
                        "cropX" => $data['x'],
                        "cropY" => $data['y'],
                        "cropW" => $data['w'],
                        "cropH" => $data['h'],
                        "tempFile" => $tempFile,
                        "originalFile" => $originalFileName,
                        "extension" => $ext
                    );

                    $media = $this->get('cutter')->cutImage($cropOptions);

                    if ('profile' == $type) {
                        $idol->setImage($media);
                    } else {
                        $idol->setSplash($media);
                    }

                    $em->persist($idol);
                    $em->flush();

                    $this->get('session')->setFlash('success', $this->trans('upload_sucess'));
                    $finish = true;
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo foto de perfil'));
            }
        }

        return array(
            'idol' => $idol,
            'form' => $form->createView(),
            'tempFile' => $tempFile,
            'originalFile' => $originalFileName,
            'ext' => $ext,
            'finish' => $finish,
            'realWidth' => $realWidth,
            'realHeight' => $realHeight,
            'type' => $type
        );
    }

    private function _createForm()
    {
        $defaultData = array();
        $collectionConstraint = new Collection(array(
            'x' => array(),
            'y' => array(),
            'w' => array(),
            'h' => array()
        ));
        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
            ->add('x', 'hidden', array('required' => false, 'data' => 0))
            ->add('y', 'hidden', array('required' => false, 'data' => 0))
            ->add('w', 'hidden', array('required' => false, 'data' => 0))
            ->add('h', 'hidden', array('required' => false, 'data' => 0))
            ->getForm();
        return $form;
    }
}