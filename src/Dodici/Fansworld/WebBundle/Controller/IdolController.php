<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Idol;
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
use Application\Sonata\UserBundle\Entity\Notification;

class IdolController extends SiteController
{

    const LIMIT_SEARCH = 20;
    const LIMIT_NOTIFICATIONS = 5;
    const LIMIT_PHOTOS = 8;

    /**
     * @Route("/i/{slug}", name="idol_wall")
     * @Template
     */
    public function wallAction($slug)
    {
        $idol = $this->getRepository('Idol')->findOneBySlug($slug);
        if (!$idol) {
            throw new HttpException(404, "No existe el ídolo");
        }else
            $this->get('visitator')->visit($idol);

        $hasComments = $this->getRepository('Comment')->countBy(array('idol' => $idol->getId()));
        $hasComments = $hasComments > 0 ? true : false;

        return array('idol' => $idol, 'hasComments' => $hasComments, 'isHome' => true);
    }

    /**
     * @Route("/i/{slug}/twitter", name= "idol_twitter")
     * @Template()
     */
    public function twitterTabAction($slug)
    {
        $lastTweets = array();
        $idol = $this->getRepository('Idol')->findOneBy(array('slug' => $slug));

        if (!$idol)
            throw new HttpException(404, 'No existe el ídolo');
        else {
            $ttScreenName = $idol->getTwitter();
            if (!$ttScreenName)
                throw new HttpException(404, 'Idolo sin twitter');
            $this->get('visitator')->visit($idol);
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
