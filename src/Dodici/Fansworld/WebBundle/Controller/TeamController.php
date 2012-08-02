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

/**
 * Team controller.
 * @Route("/team")
 */
class TeamController extends SiteController
{

    const LIMIT_ITEMS = 10;

    /**
     * @Route("/{id}/{slug}", name= "team_show", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template()
     */
    public function showAction($id)
    {
        $repo = $this->getRepository('Team');
        $team = $repo->findOneBy(array('id' => $id, 'active' => true));
        $highlights = $this->getRepository('video')->highlights($team, 4);

        if (!$team)
            throw new HttpException(404, 'Equipo no encontrado');
        else
            $this->get('visitator')->visit($team);

        return array(
            'team' => $team,
            'isHome' => true,
            'highlights' => $highlights,
        );
    }

    /**
     * @Route("/{categorySlug}", name= "team_list", defaults = {"categorySlug" = null})
     * @Template()
     */
    public function listAction($categorySlug)
    {
        $category = $this->getRepository('TeamCategory')->findOneBy(array('slug' => $categorySlug));
        $categoryId = null;
        if ($category) {
            $categoryId = $category->getId();
        }
        $categories = $this->getRepository('TeamCategory')->findBy(array(), array('title' => 'desc'));
        return array(
            'categoryId' => $categoryId,
            'categories' => $categories
        );
    }

    /**
     *  @Route("/ajax/get", name = "team_get") 
     */
    public function ajaxGetTeams()
    {
        $request = $this->getRequest();

        $page = (int) $request->get('page', 1);

        $limit = self::LIMIT_ITEMS;

        $page--;
        $offset = $page * $limit;

        $response = array();
        $params = array();
        $params['active'] = true;

        /*
          TODO: arreglar esto para que funcione con el m/m

          $teamcategory = $request->get('category', null);
          if($teamcategory == 'null'){
          $teamcategory = null;
          }
          if ($teamcategory)
          $params['teamcategories'] = array($teamcategory);
         */

        if ($page == 0) {
            $teams = $this->getRepository('Team')->findBy($params, array('title' => 'DESC'));

            foreach ($teams as $team) {
                $response['teams'][] = array(
                    'id' => $team->getId(),
                    'title' => (string) $team
                );
            }
        } else {
            $teams = $this->getRepository('Team')->findBy($params, array('fanCount' => 'DESC'), $limit, $offset);
            foreach ($teams as $team) {
                $response['images'][] = array(
                    'id' => $team->getId(),
                    'image' => $this->getImageUrl($team->getImage()),
                    'slug' => $team->getSlug(),
                    'title' => $team->getTitle()
                );
            }

            $countTotal = $this->getRepository('Team')->countBy($params);
            if ($countTotal > (($page + 1) * $limit)) {
                $response['gotMore'] = true;
            } else {
                $response['gotMore'] = false;
            }
        }

        return $this->jsonResponse($response);
    }

    /**
     * Toggle teamship
     * 
     *  @Route("/ajax/toggle", name="teamship_ajaxtoggle")
     */
    public function ajaxToggleAction()
    {
        try {
            $request = $this->getRequest();
            $idteam = intval($request->get('team'));
            $user = $this->getUser();

            if (!$user instanceof User)
                throw new \Exception('Debe iniciar sesión');

            $team = $this->getRepository('Team')->findOneBy(array('id' => $idteam, 'active' => true));
            if (!$team)
                throw new \Exception('Equipo no encontrado');

            $translator = $this->get('translator');
            $appstate = $this->get('appstate');

            $teamship = $appstate->teamshipWith($team);
            $em = $this->getDoctrine()->getEntityManager();
            if ($teamship) {
                $em->remove($teamship);
                $em->flush();

                $message = $translator->trans('You are no longer a fan of') . ' "' . (string) $team . '"';
                $buttontext = $translator->trans('add_idol');
                $isFan = false;
            } else {
                $teamship = new Teamship();
                $teamship->setAuthor($user);
                $teamship->setTeam($team);
                $em->persist($teamship);
                $em->flush();

                $message = $translator->trans('You are now a fan of') . ' "' . (string) $team . '"';
                $buttontext = $translator->trans('remove_idol');
                $isFan = true;
            }

            return $this->jsonResponse(
                            array(
                                'buttontext' => $buttontext,
                                'message' => $message,
                                'isFan' => $isFan
                            )
            );
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

    /**
     * @Route("/{slug}/twitter", name= "team_twitter")
     * @Template()
     */
    public function twitterTabAction($slug)
    {
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug, 'active' => true));
        if (!$team)
            throw new HttpException(404, 'No existe el equipo');
        else {
            $ttScreenName = $team->getTwitter();
            if (!$ttScreenName)
                throw new HttpException(404, 'Equipo sin twitter');
            $this->get('visitator')->visit($team);
        }
        
        return array('team' => $team);
    }

    /**
     *  @Route("/ajax/search/", name="team_ajaxsearch")
     */
    public function ajaxTeams()
    {
        $request = $this->getRequest();
        $idcategory = $request->get('idcategory');
        $text = $request->get('text');
        $page = $request->get('page');

        $limit = null;
        $offset = null;

        if ($page !== null) {
            $page--;
            $limit = self::LIMIT_AJAX_GET;
            $offset = $limit * $page;
        }

        $teams = $this->getRepository('Team')->matching($idcategory, $text, $limit, $offset);

        $response = array();
        foreach ($teams as $team) {
            $response[] = array(
                'id' => $team->getId(),
                'title' => $team->getTitle(),
                'image' => $this->getImageUrl($team->getImage(), 'avatar')
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     *
     * @Route("/ajax/favorite", name="team_ajaxfavorite")
     */
    public function ajaxFavorite()
    {
        $request = $this->getRequest();
        $actualTeamId = $request->get('actual', false);
        $selectedTeamId = $request->get('selected', false);

        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $teamshipRepo = $this->getRepository('Teamship');
        $teamRepo = $this->getRepository('Team');

        $actualTeam = $teamRepo->find($actualTeamId);
        $selectedTeam = $teamRepo->find($selectedTeamId);

        $response = array('error' => false);

        try {
            if ($actualTeam) {
                $actual = $teamshipRepo->findOneBy(array('author' => $user->getId(), 'team' => $actualTeam->getId()));
                if ($actual) {
                    $actual->setFavorite(false);
                    $em->persist($actual);
                }
            }

            if ($selectedTeam) {
                $selectedTeamship = $teamshipRepo->findOneBy(array('author' => $user->getId(), 'team' => $selectedTeam->getId()));
                if ($selectedTeamship) {
                    $selectedTeamship->setFavorite(true);
                    $em->persist($selectedTeamship);
                } else {
                    $newTeamship = new Teamship();
                    $newTeamship->setAuthor($user);
                    $newTeamship->setFavorite(true);
                    $newTeamship->setTeam($selectedTeam);
                    $em->persist($newTeamship);
                }
                $em->flush();
            }
        } catch (Exception $exc) {
            $response['error'] = $exc->getMessage();
        }

        return $this->jsonResponse($response);
    }

    /**
     * @Route("/{slug}/photos", name= "team_photos")
     * @Template()
     */
    public function photosTabAction($slug)
    {
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug));

        if (!$team) {
            throw new HttpException(404, "No existe el equipo");
        }else
            $this->get('visitator')->visit($team);

        $photos = $this->getRepository('Photo')->searchByEntity($team, self::LIMIT_ITEMS);
        $photosTotalCount = $this->getRepository('Photo')->countByEntity($team);

        $viewMorePhotos = $photosTotalCount > self::LIMIT_ITEMS ? true : false;

        return array(
            'team' => $team,
            'photos' => $photos,
            'gotMore' => $viewMorePhotos
        );
    }
    
    /**
     * team videos
     * @Route("/{slug}/videos", name="video_team") 
     * @Template()
     */
    public function videosTabAction($slug)
    {
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug));

        if (!$team) {
            throw new HttpException(404, "No existe el equipo");
        } else {
            $this->get('visitator')->visit($team);
        }

        $videoRepo = $this->getRepository('Video');
        $videoRepo instanceof VideoRepository;

        $user = $this->getUser();

        $videos = $videoRepo->search(null, $user, self::LIMIT_ITEMS, null, null, null, null, null, null, 'default', $team);
        $countAll = $videoRepo->countSearch(null, $user, self::LIMIT_ITEMS, null, null, null, null, null, null, $team);

        $addMore = $countAll > self::LIMIT_ITEMS ? true : false;

        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'user' => $user,
            'team' => $team
        );
    }
}
