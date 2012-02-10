<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Request;

/**
 * Home controller.
 */
class ContestController extends SiteController
{

    const commentsLimit = 6;
    const contestLimit = 20;

    /**
     * Site's home
     * 
     * @Template
     */
    public function indexAction()
    {

        return array(
        );
    }

    /**
     * 
     * @Route("/ajax/contest/comments/", name="contest_ajaxcomments")
     */
    public function ajaxCommentsAction()
    {
        $request = $this->getRequest();
        $contestId = $request->get('contestId');
        $page = $request->get('page', 0);
        $page--;

        $contestRepo = $this->getRepository('Contest');

        $contestComments = $contestRepo->findOneBy(array('id' => $contestId))->getComments();

        $chunked = array_chunk($contestComments, self::commentsLimit);

        die(json_encode($chunked[$page]));
    }

    
    /**
     * @Route("/ajax/contest/add_comment/", name = "contest_ajaxaddcomment") 
     */
    public function ajaxAddCommentAction()
    {
        $response = null;
        try {
            $request = $this->getRequest();
            $contestId = $request->get('contestId');
            $content = $request->get('content');
            $contest = $this->getRepository('Contest')->findOneBy(array('id' => $contestId));

            $user = $this->get('security.context')->getToken()->getUser();

            $newComment = new Comment();
            $newComment->setAuthor($user);
            $newComment->setContent($content);
            $newComment->setContest($contest);

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($newComment);
            $em->flush();
            
            $response = array('saved' => true);
        } catch (Exception $exc) {
            $response = array('saved' => false, 'exception' => $exc->getMessage());
        }
        
        die(json_encode($response));
    }

    /**
     * 
     */
    public function ajaxParticipateAction()
    {
        $request = $this->getRequest();
        $contest = $request->get('contestId');
    }
    
    /**
     * @Route("/contests/", name="contest_list")
     * 
     * @Template
     */
    public function listAction(){
        $contests = $this->getRepository('Contest')->findBy(array(), array('createdAt' => 'desc'), self::contestLimit);
        return array('contests' => $contests);
    }
    
    /**
     * @Route("/ajax/contest/list", name="contest_ajaxlist")
     */
    public function ajaxListAction()
    {
        $request = $this->getRequest();
        $filter = $request->get('filter');
        $page = $request->get('page', 0);
        $page--;
        $offset = $page * self::contestLimit;
        
        $contests = $this->getRepository('Contest')->findBy(array('type' => $filter), array('createdAt' => 'desc'), self::contestLimit, $offset);
        
        $response = array();
        foreach($contests as $contest){
            $response[$contest->getId()]['id'] = $contest->getId();
            $response[$contest->getId()]['title'] = $contest->getTitle();
            $response[$contest->getId()]['active'] = $contest->getActive();
            $response[$contest->getId()]['content'] = $contest->getContent();
            $response[$contest->getId()]['image'] = $this->getImageUrl($contest->getImage());
            $response[$contest->getId()]['createdAt'] = $contest->getCreatedAt();
            $response[$contest->getId()]['endDate'] = $contest->getEndDate();
            $response[$contest->getId()]['type'] = $contest->getType();
            $response[$contest->getId()]['slug'] = $contest->getSlug();
        }
        
        die(json_encode($response));
    }
    
    /**
     * @Route("/contest/show/{id}", name= "contest_show", defaults = {"id" = 0})
     * @Template
     */
    public function showAction($id)
    {
        if($id>0){
            $contest = $this->getRepository('Contest')->findOneBy(array('id' => $id));
        }else{
            $contest = false;
        }
        
        
        return array('contest' => $contest);
    }
}
