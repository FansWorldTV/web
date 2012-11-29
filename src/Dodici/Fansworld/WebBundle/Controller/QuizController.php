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
use Dodici\Fansworld\WebBundle\Entity\QuizQuestion;
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
            $author = $this->getUser();
            $quizAlreadyResponded = $this->getRepository('QuizAnswer')->countBy(array('author' => $author->getId(), 'quizquestion' => $quiz->getId()));
            if ($quizAlreadyResponded > 0) {
                $response['error'] = true;
                $response['msg'] = 'already responded';
            } else {
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
            }
        } else {
            $response['msg'] = "quizid doesnt exists";
        }


        return $this->jsonResponse($response);
    }

    /**
     * @Route("/results", name="quiz_results")
     */
    public function seeResultsAction()
    {
        $request = $this->getRequest();
        $quizId = $request->get('quizid', false);

        $response = array(
            'answers' => null,
            'total' => 0
        );

        $quiz = $this->getRepository('QuizQuestion')->find($quizId);
        $quiz instanceof QuizQuestion;

        foreach ($quiz->getQuizanswers() as $answer) {
            $answer instanceof QuizAnswer;
            foreach ($answer->getOptions() as $op) {
                $op instanceof QuizOption;
                if (!isset($response['answers'][$op->getId()])) {
                    $response['answers'][$op->getId()] = 1;
                } else {
                    $response['answers'][$op->getId()]++;
                }
            }
            $response['total']++;
        }

        return $this->jsonResponse($response);
    }

}
