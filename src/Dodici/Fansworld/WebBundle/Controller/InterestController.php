<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\HasInterest;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Interest;
use Dodici\Fansworld\WebBundle\Entity\InterestCategory;
use Dodici\Fansworld\WebBundle\Entity\Teamship;

/**
 * Interest controller.
 * @Route("/interest")
 */
class InterestController extends SiteController
{

    const LIMIT_AJAX_GET = 6;

    /**
     * @Route("/edit", name="interest_edit")
     * @Template
     * @Secure(roles="ROLE_USER")
     */
    public function editAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $response = array(
            'user',
            'categories',
            'sports' => array()
        );

        $response['user'] = $user;
        $response['categories'] = $this->getRepository('InterestCategory')->findBy(array(), array('title' => 'ASC'));

        $teams = $this->getRepository('Team')->findAll();

        $sports = & $response['sports'];
        foreach ($teams as $team) {
            foreach ($team->getTeamcategories() as $category) {

                $teamship = $this->getRepository('Teamship')->findOneBy(array("author" => $user->getId(), 'team' => $team->getId()));

                if (!isset($sports[$category->getSport()->getId()])) {
                    $sports[$category->getSport()->getId()] = array(
                        'id' => $category->getSport()->getId(),
                        'title' => $category->getSport()->getTitle(),
                        'selected' => false
                    );
                }

                if ($teamship && $teamship->getFavorite()) {
                    $sports[$category->getSport()->getId()]['selected'] = $teamship->getTeam()->getId();
                }

                if (!isset($sports[$category->getSport()->getId()]['teams'])) {
                    $sports[$category->getSport()->getId()]['teams'] = array();
                }

                $sports[$category->getSport()->getId()]['teams'][] = $team;
            }
        }

        return $response;
    }

    /**
     *  get params (all optional):
     *   - idcategory
     *   - text (partial match)
     *   - iduser
     *   - excludeuser (boolean)
     *   - page
     *  @Route("/ajax/get/", name="interest_ajaxget")
     */
    public function ajaxInterests()
    {
        $request = $this->getRequest();
        $idcategory = $request->get('idcategory');
        $text = $request->get('text');
        $iduser = $request->get('iduser');
        $excludeuser = $request->get('excludeuser', false);
        $page = $request->get('page');
        $limit = null;
        $offset = null;

        if ($page !== null) {
            $page--;
            $limit = self::LIMIT_AJAX_GET;
            $offset = $limit * $page;
        }

        $interests = $this->getRepository('Interest')->matching($idcategory, $text, $iduser, $excludeuser, $limit, $offset);

        $response = array();
        foreach ($interests as $interest) {
            $response[] = array(
                'id' => $interest->getId(),
                'title' => $interest->getTitle(),
                'image' => $this->getImageUrl($interest->getImage(), 'avatar')
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     *  add an interest
     *  get params:
     *   - id (interest)
     *   - career (optional, boolean)
     *   - position (optional, string)
     *   - datefrom (optional)
     *   - dateto (optional)
     *  @Route("/ajax/add/", name="interest_ajaxadd")
     */
    public function ajaxAdd()
    {
        try {
            $request = $this->getRequest();
            $user = $this->get('security.context')->getToken()->getUser();
            if (!$user)
                throw new AccessDeniedException('No inició sesión');
            $idinterest = $request->get('id');
            $interest = $this->getRepository('Interest')->find($idinterest);
            if (!$interest)
                throw new \Exception('No existe el interés');
            $career = $request->get('career', false);
            $position = $request->get('position');
            $datefrom = $request->get('datefrom');
            $dateto = $request->get('dateto');

            $exists = $this->getRepository('HasInterest')->findBy(array('author' => $user->getId(), 'interest' => $interest->getId()));
            if ($exists)
                throw new \Exception('El usuario ya tiene el interés "' . (string) $interest . '"');

            $hasinterest = new HasInterest();
            $hasinterest->setAuthor($user);
            $hasinterest->setCareer($career);
            $hasinterest->setPosition($position);
            $hasinterest->setDateFrom($datefrom);
            $hasinterest->setDateTo($dateto);
            $hasinterest->setInterest($interest);

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($hasinterest);
            $em->flush();
            return $this->jsonResponse(array(
                        'id' => $interest->getId(),
                        'title' => $interest->getTitle(),
                        'image' => $this->getImageUrl($interest->getImage(), 'avatar'),
                        'message' => 'Se ha agregado el interés "' . (string) $interest . '"'
                    ));
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

    /**
     *  remove an interest
     *  get params:
     *   - id (interest)
     *  @Route("/ajax/delete/", name="interest_ajaxdelete")
     */
    public function ajaxDelete()
    {
        try {
            $request = $this->getRequest();
            $user = $this->get('security.context')->getToken()->getUser();
            if (!$user)
                throw new AccessDeniedException('No inició sesión');
            $idinterest = $request->get('id');
            $interest = $this->getRepository('Interest')->find($idinterest);
            if (!$interest)
                throw new \Exception('No existe el interés');

            $exists = $this->getRepository('HasInterest')->findOneBy(array('author' => $user->getId(), 'interest' => $interest->getId()));
            if (!$exists)
                throw new \Exception('El usuario no tiene el interés');
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($exists);
            $em->flush();
            return $this->jsonResponse(array('message' => 'Se ha quitado el interés "' . (string) $interest . '"'));
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

}
