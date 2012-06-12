<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Complaint;
use Dodici\Fansworld\WebBundle\Entity\QuizAnswer;
use Dodici\Fansworld\WebBundle\Entity\QuizOption;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Quiz controller.
 * 
 * @Route("/quiz")
 */
class QuizController extends SiteController
{

    public function widgetAction($quizId)
    {
        $quiz = $this->getRepository('QuizQuestion')->find($quizId);

        return array('quiz' => $quiz);
    }

    /**
     *
     * @Route("/vote", name="quiz_vote")
     */
    public function voteAction()
    {
        $request = $this->getRequest();
        $optionsVote = $request->get('vote', false);
        $quizId = $request->get('quizid', false);

        $response = array('error' => true);

        $quiz = $this->getRepository('QuizQuestion')->find($quizId);
        if ($quiz) {
            $author = $this->get('security.context')->getToken()->getUser();

            try {
                $quizAnswer = new QuizAnswer();
                $quizAnswer->setAuthor($author);
                $quizAnswer->setQuizquestion($quiz);

                foreach ($quiz->getQuizoptions() as $op) {
                    if (array_key_exists($op->getId(), $optionsVote)) {
                        $quizAnswer->addQuizOption($op);
                    }
                }

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($quizAnswer);
                $em->flush();

                $response['error'] = false;
            } catch (Exception $exc) {
                $response['msg'] = $exc->getMessage();
            }
        } else {
            $response['msg'] = "quizid doesnt exists";
        }


        return $this->jsonResponse($response);
    }

    public function seeResultsAction()
    {
        $request = $this->getRequest();
        $quizId = $request->get('quizid', false);
        $response = array(
            'answers' => null
        );

        $quizAnswers = $this->getRepository('QuizAnswer')->findBy(array('quizquestion' => $quizId), array('createdAt' => 'desc'));
        foreach ($quizAnswers as $answer) {
            $response['answers'][$answer->getQuizquestion()->getId()] = $answer->getOptions();
        }

        return $this->jsonResponse($response);
    }

}
