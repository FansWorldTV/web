<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\HasUser;

use Dodici\Fansworld\WebBundle\Entity\Idolship;

use Dodici\Fansworld\WebBundle\Entity\Album;

use Symfony\Component\HttpFoundation\File\File;

use Application\Sonata\MediaBundle\Entity\Media;

use Dodici\Fansworld\WebBundle\Entity\ForumPost;

use Dodici\Fansworld\WebBundle\Entity\Liking;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Dodici\Fansworld\WebBundle\Controller\SiteController;


use Symfony\Component\HttpFoundation\Request;

/**
 * Home controller.
 */
class HomeController extends SiteController
{
    /**
     * Site's home
     * @Template
     */
    public function indexAction()
    {
		$em = $this->getDoctrine()->getEntityManager();
		$userrepo = $this->getRepository('User');
		$user = $userrepo->find(12);
		
		$vidrepo = $this->getRepository('Video');
		
		\Doctrine\Common\Util\Debug::dump($vidrepo->countSearchText('caca', $user));
		\Doctrine\Common\Util\Debug::dump($vidrepo->countByTag($this->getRepository('Tag')->find(2), $user));
		exit;
		
		//\Doctrine\Common\Util\Debug::dump($userrepo->SearchIdolFront($user, 'a')); exit;
		
		//\Doctrine\Common\Util\Debug::dump($this->get('user.location')->parseLocation('Buenos Aires, Argentina')); exit;
		
		/*
		$date = new \DateTime('03/26/1982'); var_dump($date); exit;
		
		$apiservice = $this->get('fos_facebook.api');
		var_dump($apiservice->api('/me')); exit;*/
		/*
		$idolship = $this->getRepository('Idolship')->find(2);
		$em->remove($idolship);
		$em->flush(); exit;
		
		$userrepo = $this->getRepository('User');
		$user = $userrepo->find(9);
		$userb = $userrepo->find(2);
		
		$idolship = new Idolship();
		$idolship->setAuthor($user);
		$idolship->setTarget($userb);
		$em->persist($idolship);
		$em->flush(); exit;*/
		
		/*
		$album = new Album();
		$album->setTitle('hi');
		$album->setContent('ho');
		$album->setAuthor($this->get('security.context')->getToken()->getUser());
		$em->persist($album); $em->flush(); exit;*/
		
		/*$importer = $this->get('contact.importer');
		var_dump($importer->import('hemeranyx9@gmail.com','ancient666','gmail')); exit;*/
		
    	
		/*$apiservice = $this->get('fos_facebook.api');
		var_dump($apiservice->api('/me/friends')); exit;*/
    	/*$commrep = $this->getRepository('Comment');
		$comment = $commrep->find(11);*/
		/*$userrepo = $this->getRepository('User');
		$user = $userrepo->find(9);
		
		$fr = $this->getRepository('Friendship')->find(2);
		$fr->setActive(true);
		$em->persist($fr);
		$em->flush();*/
		
		/*
		$author = $userrepo->find(9);
		
		$this->get('complainer')->complain($author, $comment, 'Obscenidad');
		
		exit;*/
		
		/*
		return $this->render(
    		'DodiciFansworldWebBundle:Comment:comment.html.twig', 
    		array('comment' => $comment)
    	);
    	exit;
    	*/
		/*
    	$notirepo = $this->getRepository('NewsPost');
		$userrepo = $this->getRepository('User');
		$noti = $notirepo->find(1);
		
		$user = $userrepo->find(2);
		$author = $userrepo->find(9);
		$comment = $this->getRepository('Comment')->find(87);
		
		$this->get('commenter')->comment($user, $noti, "hola que tal\r\ncomo va todo?<strong>hi</strong>");
		$this->get('commenter')->comment($author, $noti, "hola que tal\r\ncomo va todo?<strong>hi</strong>");
		$this->get('commenter')->comment($author, $comment, "hola que tal\r\ncomo va todo?<strong>hi</strong>"); exit;*/
		/*
		exit;
		
		
    	
    	$userrepo = $this->getRepository('User');
		$forumrepo = $this->getRepository('ForumThread');
		
    	$em = $this->getDoctrine()->getEntityManager();
    	$post = new ForumPost();
    	$post->setAuthor($userrepo->find(2));
    	$post->setContent('glarb');
    	$post->setForumthread($forumrepo->find(1));
    	
    	$em->persist($post);
    	$em->flush();
    	
    	
		
		\Doctrine\Common\Util\Debug::dump($userrepo->byThread($forumrepo->find(1))); exit;
    	
    	//        $user = $this->get('security.context')->getToken()->getUser();
//        /*
//        $tagger = $this->get('tagger');
//        $contest = $this->getRepository('NewsPost')->find(1);
//        
//        $tagger->tag($user, $contest, array('pedo', $user, 'caca', 'garfio'));
//        
//        exit;*/
//        
//        $em = $this->getDoctrine()->getEntityManager();
//        
        /*$repo = $this->getRepository('Liking');
        $liking = $repo->find(4);
        $em->remove($liking);
        $em->flush(); exit;*/
//    	 
//    	$repo = $this->getRepository('Album');
//        $album = $repo->find(1);
//        
//        $liking = new Liking();
//        $liking->setAlbum($album);
//        $liking->setAuthor($user);
//        
//        
//        $em->persist($liking);
//        $em->flush();
//        
//        /*return new Response(json_encode(array('hi' => 'pedo')));
//    	$repo = $this->getRepository('User');
//        \Doctrine\Common\Util\Debug::dump($repo->CountSearchFront($user, 'a', null)); exit;
//        exit;
//    	
//    	$apiservice = $this->get('fos_facebook.api');
//		var_dump($apiservice->api('/me/friends')); exit;*/
        return array(
            	'test' => $this->getRepository('Photo')->find(1)
            );
    }
    
}
