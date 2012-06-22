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

        if (!$team)
            throw new HttpException(404, 'Equipo no encontrado');
        else
            $this->get('visitator')->addVisit($team);

        return array(
            'team' => $team,
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
            
            foreach($teams as $team){
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
            $user = $this->get('security.context')->getToken()->getUser();

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
        $lastTweets = array();
        $team = $this->getRepository('Team')->findOneBy(array('slug' => $slug, 'active' => true));

        if (!$team)
            throw new HttpException(404, 'Equipo no encontrado');
        else {
            $ttScreenName = $team->getTwitter();
            if (!$ttScreenName)
                throw new HttpException(404, 'Equipo sin twitter');
        }

        $lastTweetsTemp = $this->get('fos_twitter.api')->get('statuses/user_timeline', array(
            'screen_name' => $ttScreenName,
            'count' => 10
                ));
        foreach ($lastTweetsTemp as $tweet) {
            $lastTweets[] = array(
                'text' => $tweet->text,
                'user' => $tweet->user->screen_name,
                'retweeted' => ($tweet->retweet_count > 0) ? true : false
            );
        }
        return array('lastTweets' => $lastTweets);
    }

}
