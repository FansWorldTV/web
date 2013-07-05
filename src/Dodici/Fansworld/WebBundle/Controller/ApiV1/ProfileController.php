<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\ApiV1\BaseController;
use Dodici\Fansworld\WebBundle\Entity\HasGenres;

/**
 * API controller - Profile
 * V1
 * @Route("/api_v1")
 */
class ProfileController extends BaseController
{
    /**
     * Idol/Team - addgenre
     *
     * @Route("/{entity}/{id}/addgenres", name="api_v1_idol_addgenre", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - genre_id: int
     *
    */
    public function addGenreAction($entity, $id)
    {
        try {
                $request = $this->getRequest();
                $genre_id = $request->get('genre_id');

                $genre = $this->getRepository('Genre')->find($genre_id);
                if (!$genre) throw new HttpException(404, 'Genre not found');

                $entity = ucwords($entity);
                if ($entity != 'Idol' && $entity != 'Team') throw new HttpException(401, 'Invalid entity');

                $ent = $this->getRepository($entity)->find($id);
                if (!$ent) throw new HttpException(404, $entity.' not found');

                $em = $this->getDoctrine()->getEntityManager();

                $method = 'set'.$entity;
                $hasgenre = new HasGenres();
                $hasgenre->$method($ent);
                $hasgenre->setGenre($genre);

                $ent->addHasGenre($hasgenre);
                $em->persist($ent);
                $em->flush();

                return $this->result(true);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

}