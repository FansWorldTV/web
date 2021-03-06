<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Twitter controller.
 * @Route("/twitter")
 */
class TwitterController extends SiteController
{

    /**
     * Set access token
     * @Route("/tokenize", name="twitter_tokenize")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function tokenizeAction()
    {
        $request = $this->getRequest();
        $t = $this->get('fos_twitter.service');
        $accesstoken = $t->getAccessToken($request);

        if ($accesstoken) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $user->setTwitter($accesstoken['screen_name']);
                $user->setTwitterid($accesstoken['user_id']);
                $user->setTwittertoken($accesstoken['oauth_token']);
                $user->setTwittersecret($accesstoken['oauth_token_secret']);
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($user);
                $em->flush($em);
            }
        }

        return array(
            'token' => $accesstoken
        );
    }

    /**
     * Redirect to Twitter with callback
     * @Route("/redirect", name="twitter_redirect")
     * @Secure(roles="ROLE_USER")
     */
    public function redirectAction()
    {
        $request = $this->getRequest();
        $t = $this->get('fos_twitter.service');
        $t->setCallbackRoute($this->get('router'), 'twitter_tokenize');
        $url = $t->getLoginUrl($request);

        return $this->redirect($url);
    }

    /**
     * user and idol tab
     * @Template()
     */
    public function lastTweetsAction($entity)
    {
        $response = array(
            'entity' => $entity,
            'lastTweets' => array()
        );

        $lastTweets = $this->get('fos_twitter.api')->get('statuses/user_timeline', array(
            'screen_name' => $entity->getTwitter(),
            'count' => 10
                ));


        $pattern = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';

        if (is_array($lastTweets)) {
            foreach ($lastTweets as $tweet) {
                $response['lastTweets'][] = array(
                    'text' => preg_replace($pattern, "<a target=\"_blank\" href=\"\\0\" rel=\"nofollow\">\\0</a>", $tweet->text),
                    'user' => $tweet->user->screen_name,
                    'retweeted' => ($tweet->retweet_count > 0) ? true : false
                );
            }
        }

        return $response;
    }

}
