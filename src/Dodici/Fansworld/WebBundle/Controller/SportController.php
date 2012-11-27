<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Teamship;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;
use Dodici\Fansworld\WebBundle\Entity\Team;
use Dodici\Fansworld\WebBundle\Entity\Sport;
use Dodici\Fansworld\WebBundle\Entity\TeamCategory;

/**
 * Team controller.
 * @Route("/sport")
 */
class SportController extends SiteController
{

    const LIMIT_ITEMS = 10;

    

    /**
     *  @Route("/ajax/get/", name = "sport_getteamcategories") 
     */
    public function ajaxGetTeamcategories()
    {
        $request = $this->getRequest();

        $response = array();
        $sportId = $request->get('sportId', null);
        
        if ($sportId != null) {
            $teamcategories = $this->getRepository('TeamCategory')->findBy(array('sport' => $sportId));
            foreach ($teamcategories as $teamcategory) {
                $response['categories'][] = array(
                    'id' => $teamcategory->getId(),
                    'title' => $teamcategory->getTitle()
                );
            }
        } 
        return $this->jsonResponse($response);
    }

    
}
