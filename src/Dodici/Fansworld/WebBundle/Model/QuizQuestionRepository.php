<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\Query\ResultSetMapping;

use Doctrine\ORM\EntityRepository;

/**
 * QuizQuestionRepository
 */
class QuizQuestionRepository extends CountBaseRepository
{
	/**
     * Get results, count by answer
     * 
     * @param \Dodici\Fansworld\WebBundle\Entity\QuizQuestion $quizquestion
     */
    public function answerCounts(\Dodici\Fansworld\WebBundle\Entity\QuizQuestion $quizquestion)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('optionid', 'id');
        $rsm->addScalarResult('optionname', 'option');
        $rsm->addScalarResult('answers', 'count');
        $rsm->addScalarResult('total', 'total');

        return $this->_em->createNativeQuery('
    	SELECT
			qo.title as optionname,
			qo.id as optionid,
			COUNT(qap.answer_id) as answers,
			(SELECT COUNT(*) FROM quiz_answer_option qqap INNER JOIN quizanswer qqa ON qqap.answer_id = qqa.id AND qqa.quizquestion_id = 1) as total
		FROM
			quiz_answer_option qap
			INNER JOIN quizoption qo ON qo.id = qap.option_id
			INNER JOIN quizquestion qq ON qq.id = qo.quizquestion_id
		WHERE
			qq.active
			AND qq.id = :quizquestionid
		GROUP BY qap.option_id
		ORDER BY qo.number asc
        ', $rsm)
                        ->setParameter('quizquestionid', $quizquestion->getId())
                        ->getResult();
    }
}