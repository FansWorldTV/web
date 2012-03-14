<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\HasUser;

use Dodici\Fansworld\WebBundle\Entity\Idolship;

use Dodici\Fansworld\WebBundle\Entity\Album;

use Symfony\Component\HttpFoundation\File\File;

use Application\Sonata\MediaBundle\Entity\Media;

use Dodici\Fansworld\WebBundle\Entity\ForumPost;

use Dodici\Fansworld\WebBundle\Entity\Liking;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Dodici\Fansworld\WebBundle\Controller\SiteController;


use Symfony\Component\HttpFoundation\Request;

/**
 * Home controller.
 */
class HomeController extends SiteController
{
    /**
     * Site's home
     * @Template
     */
    public function indexAction()
    {
		
        return array(
            );
    }
    
}
