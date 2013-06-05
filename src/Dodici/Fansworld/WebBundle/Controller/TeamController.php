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
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;

/**
 * Team controller.
 * @Route("/team")
 */
class TeamController extends SiteController
{

    const LIMIT_ITEMS = 10;

    /**
     * @Route("/{id}/next", name="team_next")
     */
    public function nextAction($id)
    {
        $team = $this->getRepository('Team')->find($id);
        $next = $this->getRepository('Team')->next($team);


        return $this->forward('DodiciFansworldWebBundle:Team:videosTab', array('slug'=> $next->getSlug()));
    }

    /**
     * @Route("/{id}/previous", name="team_previous")
     */
    public function previousAction($id)
    {
        $team = $this->getRepository('Team')->find($id);
        $previous = $this->getRepository('Team')->previous($team);

        return $this->forward('DodiciFansworldWebBundle:Team:videosTab', array('slug'=> $previous->getSlug()));
    }

    /**
     * @Route("/list/{categorySlug}", name= "team_list", defaults = {"categorySlug" = null})
     * @Template()
     */
    public function listAction($categorySlug)
    {
        $categories = $this->getRepository('TeamCategory')->findBy(array(), array('title' => 'desc'));
        $videoHighlights = $this->getRepository('Video')->findBy(array('highlight' => true), array('createdAt' => 'desc'), 4);
        $popularTeams = $this->getRepository('Team')->findBy(array(), array('fanCount' => 'desc'), 3);
        $countAll = $this->getRepository('Team')->countBy(array('active' => true));

        return array(
            'categories' => $categories,
            'videoHighlights' => $videoHighlights,
            'popularTeams' => $popularTeams,
            'gotMore' => $countAll > self::LIMIT_ITEMS ? true : false
        );
    }

    /**
     *  @Route("/ajax/get", name = "team_get")
     */
    public function ajaxGetTeams()
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $page = (int) $request->get('page', 1);
        $category = $request->get('category', null);
        if ($category == 'null') {
            $category = null;
        }

        $limit = self::LIMIT_ITEMS;

        $offset = ($page - 1) * $limit;

        $response = array();

        $teamRepo = $this->getRepository('Team');

        if (is_null($category)) {
            $teams = $teamRepo->findBy(array('active' => true), array('title' => 'desc'), $limit, $offset);
            $countAll = $teamRepo->countBy(array('active' => true));
        } else {
            $teams = $teamRepo->matching($category, null, $user, $limit, $offset);
            $countAll = $teamRepo->countMatching($category);
        }

        $response['gotMore'] = $countAll > ($limit * $page) ? true : false;

        foreach ($teams as $team) {
            $team instanceof Team;
            $idols = array();

            $c = 0;
            foreach ($team->getIdolCareers() as $ic) {
                $c++;
                $idol = $ic->getIdol();
                $idols[] = array(
                    'name' => (string) $idol,
                    'url' => $this->generateUrl('idol_land', array('slug' => $idol->getSlug()))
                );
                if ($c == 2) {
                    break;
                }
            }

            if ($user) {
                $teamship = $this->getRepository('Teamship')->findOneBy(array('author' => $user->getId(), 'team' => $team->getId())) ? true : false;
            } else {
                $teamship = false;
            }

            if (strlen($team->getTitle()) > 70) {
                $teamTitle = substr($team->getTitle(), 0, 21) . "...";
            } else {
                $teamTitle = $team->getTitle();
            }

            $router = $this->get('router');
            $typesUrl = array(
                'photos' => null,
                'videos' => null,
                'fans' => null
            );
            foreach ($typesUrl as $key => $value) {
                $typesUrl[$key] = $router->generate('team_' . $key, array('slug' => $team->getSlug()));
            }

            $response['teams'][] = array(
                'id' => $team->getId(),
                'title' => $teamTitle,
                'fanCount' => $team->getFanCount(),
                'videoCount' => $team->getVideoCount(),
                'photoCount' => $team->getPhotoCount(),
                'isFan' => $this->get('fanmaker')->isFan($team, $this->getUser()),
                'image' => $this->getImageUrl($team->getImage()),
                'idols' => $idols,
                'teamship' => $teamship,
                'url' => $this->generateUrl('team_land', array('slug' => $team->getSlug())),
                'photosUrl' => $typesUrl['photos'],
                'videosUrl' => $typesUrl['videos'],
                'fansUrl' => $typesUrl['fans']
            );
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
                $this->get('fanmaker')->addFan($team, $user);

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
     *  @Route("/ajax/search", name="team_ajaxsearch")
     */
    public function ajaxTeams()
    {
        $request = $this->getRequest();
        $idcategory = $request->get('idcategory', null);
        $sport = $request->get('idsport', null);
        $text = $request->get('text');
        $page = $request->get('page');
        $user = $this->getUser();

        $limit = null;
        $offset = null;

        if ($page !== null) {
            $page--;
            $limit = self::LIMIT_AJAX_GET;
            $offset = $limit * $page;
        }

        $teams = $this->getRepository('Team')->matching($idcategory, $text, $user, $limit, $offset, $sport);

        $response = array();
        foreach ($teams as $team) {
            $response[] = array(
                'id' => $team->getId(),
                'title' => $team->getShortName(),
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
        $isRemove = $request->get('remove', false);

        $user = $this->getUser();
        $em = $this->getDoctrine()->getEntityManager();

        $teamshipRepo = $this->getRepository('Teamship');
        $teamRepo = $this->getRepository('Team');

        $actualTeam = $teamRepo->find($actualTeamId);
        $selectedTeam = $teamRepo->find($selectedTeamId);

        $response = array('error' => false);

        try {

            if ($isRemove) {
                $teamship = $teamshipRepo->findOneBy(array('author' => $user->getId(), 'team' => $actualTeam->getId()));
                $teamship->setFavorite(false);
                $em->persist($teamship);
                $em->flush();

                return $this->jsonResponse($response);
            }


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
                    $this->get('fanmaker')->addFan($selectedTeam, $user, false, true);
                }
                $em->flush();
            }
        } catch (\Exception $exc) {
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
     * @Route("/{slug}", name="team_land")
     * @Route("/{slug}/videos", name="team_videos")
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

        $user = $this->getUser();

        $videos = $videoRepo->search(null, $user, self::LIMIT_ITEMS, null, null, null, null, null, null, 'default', $team);
        $countAll = $videoRepo->countSearch(null, $user, null, null, null, null, null, $team);

        $addMore = $countAll > self::LIMIT_ITEMS ? true : false;

        $sorts = array(
            'id' => 'toggle-video-types',
            'class' => 'list-videos',
            'list' => array(
                array(
                    'name' => 'Destacados',
                    'dataType' => 0,
                    'class' => '',
                ),
                array(
                    'name' => 'Más vistos',
                    'dataType' => 1,
                    'class' => '',
                ),
                array(
                    'name' => 'Más vistos del día',
                    'dataType' => 3,
                    'class' => '',
                ),
                array(
                    'name' => 'Populares',
                    'dataType' => 2,
                    'class' => 'active',
                )
            )
        );

        return array(
            'videos' => $videos,
            'addMore' => $addMore,
            'user' => $user,
            'team' => $team,
            'sorts' => $sorts,
            'isHome' => true
        );
    }

    /**
     *  @Route("/{slug}/info", name="team_info")
     *  @Template()
     */
    public function infoTabAction($slug)
    {
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug));

        if (!$team) {
            throw new HttpException(404, "No existe el ídolo");
        } else {
            $this->get('visitator')->visit($team);
        }

        $user = $this->getUser();
        $teamData = array('title', 'shortname', 'letters', 'stadium', 'website', 'content', 'nicknames', 'foundedAt', 'country');

        return array(
            'user' => $user,
            'teamData' => $teamData,
            'team' => $team
        );
    }

    /**
     * @Route("/{slug}/fans", name="team_fans")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function fansTabAction($slug)
    {
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug));
        if (!$team) {
            throw new HttpException(404, "No existe el Team");
        }else
            $this->get('visitator')->visit($team);

        $fans = array(
            'ulClass' => 'fans',
            'containerClass' => 'fan-container'
        );
        $fans['list'] = $this->getRepository('User')->byTeams($team, null, 'score');

        $return = array(
            'fans' => $fans,
            'team' => $team
        );

        return $return;
    }

    /**
     * @Route("/{slug}/idols", name="team_idols")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function idolsTabAction($slug)
    {
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug));
        if (!$team) {
            throw new HttpException(404, "No existe el Team");
        }else
            $this->get('visitator')->visit($team);

        $idolships = array(
            'ulClass' => 'idols',
            'containerClass' => 'idol-container',
            'list' => $this->getRepository('Idol')->byTeam($team),
        );
        //$idolshipsCount = $this->getRepository('Idolship')->countBy(array('author' => $user->getId()));

        $return = array(
            'idolships' => $idolships,
            //'addMore' => $idolshipsCount > self::LIMIT_LIST_IDOLS ? true : false,
            'addMore' => false,
            'team' => $team,
        );

        return $return;
    }

    /**
     * @Route("/{slug}/events", name="team_eventos")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function eventosTabAction($slug)
    {
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug));
        if (!$team) {
            throw new HttpException(404, "No existe el Team");
        }else
            $this->get('visitator')->visit($team);

        $eventos = $this->getRepository('Event')->ByTeam($team);

        $return = array(
            'eventos' => $eventos,
            'team' => $team,
        );

        return $return;
    }

    /**
     * @Route("/{slug}/wall", name= "team_wall")
     * @Template()
     */
    public function wallTabAction($slug)
    {
        $repo = $this->getRepository('Team');
        $team = $repo->findOneBy(array('slug' => $slug, 'active' => true));
        if (!$team)
            throw new HttpException(404, 'Equipo no encontrado');
        else
            $this->get('visitator')->visit($team);

        $highlights = $this->getRepository('video')->highlights($team, 4);




        return array(
            'team' => $team,
            'highlights' => $highlights,
        );
    }

    /**
     * @Route("/change/image", name="team_change_imageSave")
     * @Secure(roles="ROLE_ADMIN")
     * @Template
     */
    public function changeImageSaveAction()
    {
        $request = $this->getRequest();
        $teamId = $request->get('team', null);
        $em = $this->getDoctrine()->getEntityManager();

        $tempFile = $request->get('tempFile');
        $originalFileName = $request->get('originalFile');
        $realWidth = $request->get('width');
        $realHeight = $request->get('height');
        $type = $request->get('type');

        $lastdot = strrpos($originalFileName, '.');
        $originalFile = substr($originalFileName, 0, $lastdot);
        $ext = substr($originalFileName, $lastdot);

        $finish = false;
        $form = $this->_createForm();

        if($teamId){
            $team = $this->getRepository('Team')->find($teamId);
        }else{
            throw new Exception('No Team');
        }

        if ($request->getMethod() == 'POST') {
            try {
                $form->bindRequest($request);
                $data = $form->getData();

                if ($form->isValid()) {
                    $mediaManager = $this->get("sonata.media.manager.media");

                    $cropOptions = array(
                        "cropX" => $data['x'],
                        "cropY" => $data['y'],
                        "cropW" => $data['w'],
                        "cropH" => $data['h'],
                        "tempFile" => $tempFile,
                        "originalFile" => $originalFileName,
                        "extension" => $ext
                    );
                    $media = $this->get('cutter')->cutImage($cropOptions);

                    if ('profile' == $type) {
                        $team->setImage($media);
                    } else {
                        $team->setSplash($media);
                    }

                    $em->persist($team);
                    $em->flush();

                    $this->get('session')->setFlash('success', $this->trans('upload_sucess'));
                    $finish = true;
                }
            } catch (\Exception $e) {
                $form->addError(new FormError('Error subiendo foto de perfil'));
            }
        }

        return array(
            'team' => $team,
            'form' => $form->createView(),
            'tempFile' => $tempFile,
            'originalFile' => $originalFileName,
            'ext' => $ext,
            'finish' => $finish,
            'realWidth' => $realWidth,
            'realHeight' => $realHeight,
            'type' => $type
        );
    }


    /**
     *  get params (all optional):
     *  - genre
     *  - limit
     *  - offset
     *  @Route("/ajax/getPopularTeams", name="teamsPopular_ajaxget")
     */
    public function popularTeamsByGenre()
    {
        $request = $this->getRequest();
        $genre = $request->get('genre');
        $limit = $request->get('limit');
        $offset = $request->get('offset');

        $teams = $this->getRepository('Team')->byGenre($genre, $limit, $offset);

        $response = array();
        foreach ($teams as $team) {
            $response[] = array(
                'id' => $team->getId(),
                'name' => (string) $team,
                'slug' => $team->getSlug(),
                'fancount' => $team->getFanCount(),
                'genre' => $this->get('serializer')->values($team->getGenre())
            );
        }

        return $this->jsonResponse($response);
    }

    private function _createForm()
    {
        $defaultData = array();
        $collectionConstraint = new Collection(array(
            'x' => array(),
            'y' => array(),
            'w' => array(),
            'h' => array()
        ));
        $form = $this->createFormBuilder($defaultData, array('validation_constraint' => $collectionConstraint))
            ->add('x', 'hidden', array('required' => false, 'data' => 0))
            ->add('y', 'hidden', array('required' => false, 'data' => 0))
            ->add('w', 'hidden', array('required' => false, 'data' => 0))
            ->add('h', 'hidden', array('required' => false, 'data' => 0))
            ->getForm();
        return $form;
    }
}
