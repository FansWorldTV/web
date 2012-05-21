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
        $idol = $this->getRepository('Slug')->findOneBySlug($slug);
        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }

        $hasComments = $this->getRepository('Comment')->countBy(array('idol' => $idol->getId()));
        $hasComments = $hasComments > 0 ? true : false;

        return array('idol' => $idol, 'hasComments' => $hasComments);
    }

}
