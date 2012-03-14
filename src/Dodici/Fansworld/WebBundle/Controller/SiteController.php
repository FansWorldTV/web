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
        if ($entity == 'Newspost') $entity = 'NewsPost';
    	if (strtolower($entity) == 'user') {
            return $this->getDoctrine()->getRepository("ApplicationSonataUserBundle:User");
        } else {
            return $this->getDoctrine()->getRepository("Dodici" . $environment . "Bundle:" . $entity);
        }
    }

    public function getImageUrl($media, $sizeFormat = 'small')
    {
        $imageUrl = null;
        $request = $this->getRequest();

        $host = 'http://' . $request->getHost();

        if ($media) {
            $mediaService = $this->get('sonata.media.pool');
            
            $provider = $mediaService->getProvider($media->getProviderName());

            $format = $provider->getFormatName($media, $sizeFormat);
            $imageUrl = $provider->generatePublicUrl($media, $format);
            
            return $host . $imageUrl;
        }
        
        return false;
    }
    
    public function jsonResponse($response)
    {
        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function securityCheck($entity)
    {
    	$user = $this->get('security.context')->getToken()->getUser();
    	
    	if (!$entity)
            throw new HttpException(404, 'Contenido no encontrado');
            
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
