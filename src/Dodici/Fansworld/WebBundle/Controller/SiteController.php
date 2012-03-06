<?php

namespace Dodici\Fansworld\WebBundle\Controller;

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

}
