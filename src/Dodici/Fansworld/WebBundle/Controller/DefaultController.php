<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends SiteController
{

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
    public function menubarAction()
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
            'countNew' => $countNew
        );
    }


    /**
     * Related column
     * @Template
     */
    public function relatedcolumnAction()
    {
        $user = $this->getUser();

        $repo = $this->getRepository('User');
        $irepo = $this->getRepository('Idol');
        $topfans = $repo->findBy(array('enabled' => true, 'type' => User::TYPE_FAN), array('score' => 'DESC', 'fanCount' => 'DESC'), 16);
        $topidols = $irepo->findBy(array('active' => true), array('fanCount' => 'DESC'), 16);

        return array('user' => $user, 'topfans' => $topfans, 'topidols' => $topidols);
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
