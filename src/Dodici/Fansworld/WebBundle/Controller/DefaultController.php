<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dodici\Fansworld\WebBundle\Entity\Team;
use Dodici\Fansworld\WebBundle\Entity\Idol;

class DefaultController extends SiteController
{


    const IDOLS_LIMIT = 12;
    const FANS_LIMIT = 12;
    const TEAMS_LIMIT = 12;
    const VIDEOS_LIMIT = 1;
    const MATCHS_LIMIT = 6;

    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }

    /**
     * Ad Column controller action
     * @Template
     */
    public function adcolumnAction()
    {
        return array();
    }

    /**
     * Popup asks text
     * @Route("/popup/asktext", name="popup_asktext")
     * @Template
     */
    public function askTextAction()
    {
        return array();
    }

    /**
     *  Menubar controller action
     * @Template
     */
    public function menubarAction($sectionSelected = false)
    {
        $notifications = array();
        $user = $this->getUser();
        $countNew = 0;
        if ($user instanceof User) {
            $notifications = $this->getRepository('Notification')->findBy(array('target' => $user->getId()), array('createdAt' => 'desc'), 5);
            foreach($notifications as $notification){
                if(!$notification->getReaded()){
                    $countNew++;
                }
            }
        }
        return array(
            'user' => $user,
            'notifications' => $notifications,
            'countNew' => $countNew,
            'sectionSelected' => $sectionSelected
        );
    }


    /**
     * Related column
     * @Template
     */
    public function relatedcolumnAction($entity=null)
    {
        $user = $this->getUser();

        if (!$entity) {
            // No entity
            $repo = $this->getRepository('User');
            $irepo = $this->getRepository('Idol');
            $trepo = $this->getRepository('Team');
            $topfans = $repo->findBy(array('enabled' => true, 'type' => User::TYPE_FAN), array('score' => 'DESC', 'fanCount' => 'DESC'), self::IDOLS_LIMIT);
            $topidols = $irepo->findBy(array('active' => true), array('fanCount' => 'DESC'), self::FANS_LIMIT);
            $teams = $trepo->findBy(array('active' => true), array('fanCount' => 'DESC'), self::TEAMS_LIMIT);
            $videos = $this->getRepository('Video')->search(null, $user, self::VIDEOS_LIMIT, null, null, null, null, null, null, 'default');

            $matchs =  $this->getRepository('Event')->findBy(array('finished' => false), array('fromtime' => 'desc'), self::MATCHS_LIMIT);

        } else {

            if ($entity instanceof User) {
                // User Entity
                $idolshipRepo = $this->getRepository('Idolship');

                // Related Idols to User Entity
                $idolships = $idolshipRepo->findBy(array('author' => $entity->getId()), array('favorite' => 'desc', 'score' => 'desc', 'createdAt' => 'desc'), self::IDOLS_LIMIT);
                $topidols = array();
                foreach ($idolships as $idolship) {
                    array_push($topidols, $idolship->getIdol());
                }

                // Related Fans to User entity
                $topfans = $this->getRepository('User')->fans($entity, true, self::FANS_LIMIT);

                // Related Teams to User Entity
                $teamshipRepo = $this->getRepository('Teamship');
                $teamships = $teamshipRepo->findBy(array('author' => $entity->getId()), array('favorite' => 'desc', 'score' => 'desc', 'createdAt' => 'desc'), self::TEAMS_LIMIT);
                $teams = array();
                foreach ($teamships as $teamship) {
                    array_push($teams, $teamship->getTeam());
                }

                // Related Video
                $videos = $this->getRepository('Video')->findBy(array('author' => $entity->getId(), 'active' => true), array('createdAt' => 'desc'), self::VIDEOS_LIMIT);

                $matchs = array();
            } else {

                if ($entity instanceof Team) {
                    // Team Entity

                    $teams = array();
                    // Related Teams to Team **
                    //$teams = $this->getRepository('Team')->getSimilar($entity);

                    $matchs = array();

                    // Related Idols to Team Entity
                    $topidols = $this->getRepository('Idol')->byTeam($entity, self::IDOLS_LIMIT);

                    // Related Fans to Team Entity
                    $topfans = $this->getRepository('User')->byTeams($entity, self::FANS_LIMIT);
                } else {
                    // Idol Entity

                    $teams = array();
                    $topidols = array();
                    $matchs = array();

                    // Related Teams to Idol **
                    //$teams = $this->getRepository('Idol')->relatedTeams($entity);

                    // Related Idols to Idol **
                    //$topidols = $this->getRepository('Idol')->commonIdols($entity);

                    // Related Fans to Idol Entity
                    $topfans = $this->getRepository('User')->byIdols($entity, self::IDOLS_LIMIT);
                }

                // Related videos to Team or Idol Entity
                $videoRepo = $this->getRepository('Video');
                $videos = $videoRepo->search(null, $user, self::VIDEOS_LIMIT, null, null, null, null, null, null, 'default', $entity);
            }
        }

        return array('user' => $user, 'topfans' => $topfans, 'topidols' => $topidols, 'teams' => $teams, 'videos' => $videos, 'matchs' => $matchs);
    }

    /**
     * Top controller action
     * @Template
     */
    public function topAction()
    {
        $usercount = $this->getRepository('User')->countBy(array('enabled' => true, 'type' => User::TYPE_FAN));
        return array('usercount' => $usercount);
    }

    /**
     * force mobile
     *
     * @Route("/mobile/{value}", requirements={"value"="yes|no"}, name="force_mobile")
     */
    public function forcemobileAction($value)
    {
        $request = $this->getRequest();
        $host = $request->getHost();
        if (strpos($host, 'm.') === 0)
            $host = substr($host, 2);

        if ($value == 'yes') {
            $url = 'http://m.' . $host . $this->generateUrl('homepage');
        } else {
            $url = 'http://' . $host . $this->generateUrl('homepage');
        }

        $response = new RedirectResponse($url);
        $cookie = new Cookie('force' . $value . 'mobile', '1', time() + (3600 * 48), '/', $host);
        $response->headers->setCookie($cookie);
        if ($value == 'yes') {
            $cookie = new Cookie('forcenomobile', '0', time() + (3600 * 48), '/', $host);
            $response->headers->setCookie($cookie);
        } else {
            $cookie = new Cookie('forceyesmobile', '0', time() + (3600 * 48), '/', $host);
            $response->headers->setCookie($cookie);
        }
        return $response;
    }

    /**
     * force mobile
     *
     * @Route("/gethost", name="gethost")
     */
    public function hostAction()
    {
        $request = $this->getRequest();
        $host = $request->getHost();
        $schema = $request->getScheme();
        return new Response($schema . '://' . $host);
    }

	/**
     * Toolbar controller action
     * @Template
     */
    public function toolbarAction()
    {
        $user = $this->getUser();
        $request = $this->getRequest();
        $hide_login = $request->get('hide_login', false);
        $hide_ad = $request->get('hide_ad', false);
        return array(
            'user' => $user,
            'hide_login' => $hide_login,
            'hide_ad' => $hide_ad
        );
    }

    /**
     * Sidebar videos
     * @Template
     */
    public function sidebarvideosAction($entity)
    {
        $loggedUser = $this->getUser();
        $numOfResult = 4;

        if ($entity instanceof User) {
            $entitySearch = null;
        } else {
            $entitySearch = $entity;
        }

        $videos = $this->getRepository('Video')->search(
            null,
            $loggedUser,
            $numOfResult,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $entitySearch
        );
        return array('videos' => $videos);
    }

}
