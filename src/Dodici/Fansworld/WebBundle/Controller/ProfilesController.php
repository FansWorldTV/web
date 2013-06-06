<?php
/**
 * Created by JetBrains PhpStorm.
 * User: nahuel
 * Date: 03/06/13
 * Time: 17:38
 * To change this template use File | Settings | File Templates.
 */
namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class ProfilesController
 * @package Dodici\Fansworld\WebBundle\Controller
 * @Route("/profiles")
 */
class ProfilesController extends SiteController {

    /**
     * @Route("", name="profiles_index")
     * @Template()
     */
    public function indexAction()
    {
        return array(
            'genres' => $this->getRepository('Genre')->getParents(),
            'profiles' => $this->getRepository('Idol')->findBy(array(), null, 30)
        );
    }
}
