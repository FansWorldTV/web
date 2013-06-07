<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Application\Sonata\UserBundle\Entity\User;

use Dodici\Fansworld\WebBundle\Entity\Privacy;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class SiteController extends Controller
{

    public function getRepository($entity, $environment = "FansworldWeb")
    {
        $entity = ucfirst($entity);
        if ($entity == 'Newspost')
            $entity = 'NewsPost';
        if ($entity == 'Forumpost')
            $entity = 'ForumPost';
        if ($entity == 'Contestparticipant')
            $entity = 'ContestParticipant';
        if ($entity == 'Quizanswer')
            $entity = 'QuizAnswer';
        if (strtolower($entity) == 'user') {
            return $this->getDoctrine()->getRepository("ApplicationSonataUserBundle:User");
        } else {
            return $this->getDoctrine()->getRepository("Dodici" . $environment . "Bundle:" . $entity);
        }
    }

    public function getImageUrl($media, $sizeFormat = 'small')
    {
        if (!($media instanceof Media)) {
            $mediarepo = $this->getDoctrine()->getRepository("ApplicationSonataMediaBundle:Media");
            $media = $mediarepo->find($media);
        }

        return $this->get('appmedia')->getImageUrl($media, $sizeFormat);
    }

    public function jsonResponse($response, $code = 200)
    {
        $response = new Response(json_encode($response), $code);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function securityCheck($entity)
    {
    	$user = $this->getUser();

    	if (!$entity || (property_exists($entity, 'active') && !$entity->getActive()))
            throw new HttpException(404, 'Contenido no encontrado');

        if (property_exists($entity, 'privacy')) {
	    	if ($entity->getPrivacy() != Privacy::EVERYONE) {
	        	if ($user instanceof User) {
	        		if (!$this->get('appstate')->canView($entity)) {
	        			throw new AccessDeniedException('Acceso denegado');
	        		}
	        	} else {
	        		throw new AccessDeniedException('Debe iniciar sesión');
	        	}
	        }
        }
    }

    public function getUser()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        return ($user instanceof User) ? $user : null;
    }

    public function trans($term)
    {
    	return $this->get('translator')->trans($term);
    }
}
