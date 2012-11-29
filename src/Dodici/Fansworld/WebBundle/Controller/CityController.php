<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\City;
use Symfony\Component\HttpFoundation\Request;

/**
 * City controller.
 */
class CityController extends SiteController
{

    /**
     * 
     * @Route("/ajax/cities", name="cities_ajax")
     */
    public function ajaxGetCitiesAction()
    {
        try {
            $request = $this->getRequest();
            $countryid = $request->get('country');

            $cities = array();

            $cityents = $this->getRepository('City')->formChoices($countryid);
            foreach ($cityents as $ce) {
                $cities[] = array('id' => $ce->getId(), 'title' => $ce->getTitle());
            }

            $response = new Response(json_encode(array(
                                'cities' => $cities
                            )));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

}
