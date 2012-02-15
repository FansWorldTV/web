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
use Dodici\Fansworld\WebBundle\Entity\Contest;
use Dodici\Fansworld\WebBundle\Entity\ContestParticipant;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
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

        $response = new Response(json_encode($chunked[$page]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
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
            $newComment->setActive(true);
            $newComment->setPrivacy(Privacy::EVERYONE);

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($newComment);
            $em->flush();

            $response = array(
                'saved' => true,
                'comment' => array(
                    'id' => $newComment->getId(),
                    'name' => $user,
                    'content' => $content,
                    'avatar' => $this->getImageUrl($user->getImage()),
                    'createdAt' => $newComment->getCreatedAt()
                )
            );
        } catch (Exception $exc) {
            $response = array('saved' => false, 'exception' => $exc->getMessage());
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/ajax/contest/participate", name="contest_ajaxparticipate")
     */
    public function ajaxParticipateAction()
    {
        $request = $this->getRequest();
        $contest = $request->get('contestId');
        $user = $this->get('security.context')->getToken()->getUser();
        $contest = $this->getRepository('Contest')->findOneBy(array('id' => $contest));

        $isParticipant = $this->getRepository('ContestParticipant')->findOneBy(array('author' => $user, 'contest' => $contest));

        if (!$isParticipant) {
            try {
                $newParticipant = new ContestParticipant();
                $newParticipant->setAuthor($user);

                if ($contest->getType() == 2) {
                    $newParticipant->setText($request->get('text'));
                }

                $newParticipant->setContest($contest);
                $newParticipant->setWinner(false);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($newParticipant);
                $em->flush();

                $response = true;
            } catch (Exception $exc) {
                $response = false;
            }
        }else{
            $response = false;
        }


        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/contests/", name="contest_list")
     * 
     * @Template
     */
    public function listAction()
    {
        $contests = $this->getRepository('Contest')->findBy(array('active' => true), array('createdAt' => 'desc'), self::contestLimit);

        $countContests = $this->getRepository('Contest')->countBy(array('active' => true));
        $addMore = $countContests > self::contestLimit ? true : false;


        $filterType = array(
            'TYPE_PARTICIPATE' => 1,
            'TYPE_TEXT' => 2,
            'TYPE_PHOTO' => 3,
            'TYPE_VIDEO' => 4
        );

        return array('contests' => $contests, 'addMore' => $addMore, 'filterType' => $filterType);
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

        $contests = $this->getRepository('Contest')->findBy(array('active' => true, 'type' => $filter), array('createdAt' => 'desc'), self::contestLimit, $offset);
        $contestsCount = $this->getRepository('Contest')->countBy(array('active' => true, 'type' => $filter));

        $contestsCount = $contestsCount / self::contestLimit;
        $addMore = $contestsCount > $page ? true : false;

        $response = array();
        foreach ($contests as $contest) {
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

        $response = new Response(json_encode(array('contests' => $response, 'addMore' => $addMore)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/contest/show/{id}", name= "contest_show", defaults = {"id" = 0})
     * @Template
     */
    public function showAction($id)
    {
        if ($id > 0) {
            $contest = $this->getRepository('Contest')->findOneBy(array('id' => $id));
        } else {
            $contest = false;
        }


        return array('contest' => $contest);
    }

}
