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
        return $this->getDoctrine()->getRepository("Dodici" . $environment . "Bundle:" . $entity);
    }
}
