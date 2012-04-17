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
     * Sidebar controller action
     * @Template
     */
    public function sidebarAction()
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
        $user = $this->get('security.context')->getToken()->getUser();
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
     * Leftbar controller action
     * @Template
     */
    public function leftbarAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
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
     * Rightbar controller action
     * @Template
     */
    public function rightbarAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $repo = $this->getRepository('User');
        $topfans = $repo->findBy(array('enabled' => true, 'type' => User::TYPE_FAN), array('score' => 'DESC', 'friendCount' => 'DESC'), 15);
        $topidols = $repo->findBy(array('enabled' => true, 'type' => User::TYPE_IDOL), array('fanCount' => 'DESC'), 15);

        return array('user' => $user, 'topfans' => $topfans, 'topidols' => $topidols);
    }

    /**
     * Top controller action
     * @Template
     */
    public function topAction()
    {
        $usercount = $this->getRepository('User')->countBy(array('enabled' => true));
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

}
