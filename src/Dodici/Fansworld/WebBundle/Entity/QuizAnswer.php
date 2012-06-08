<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\QuizAnswer
 *
 * @ORM\Table(name="quizanswer")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\QuizAnswerRepository")
 */
class QuizAnswer
{
    /**
     * @var bigint $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Application\Sonata\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $author;

    /**
     * @var string $ip
     *
     * @ORM\Column(name="ip", type="string", length=100, nullable=true)
     */
    private $ip;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
    /**
     * @var QuizQuestion
     *
     * @ORM\ManyToOne(targetEntity="QuizQuestion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="quizquestion_id", referencedColumnName="id")
     * })
     */
    private $quizquestion;
    
    /**
     * @ORM\ManyToMany(targetEntity="QuizOption")
     * @ORM\JoinTable(name="quiz_answer_option",
     *      joinColumns={@ORM\JoinColumn(name="answer_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="option_id", referencedColumnName="id")}
     *      )
     */
    protected $options;

    public function __construct()
    {
        $this->options = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Get id
     *
     * @return bigint 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ip
     *
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set createdAt
     *
     * @param datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return datetime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set author
     *
     * @param Application\Sonata\UserBundle\Entity\User $author
     */
    public function setAuthor(\Application\Sonata\UserBundle\Entity\User $author)
    {
        $this->author = $author;
    }

    /**
     * Get author
     *
     * @return Application\Sonata\UserBundle\Entity\User 
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Add options
     *
     * @param Dodici\Fansworld\WebBundle\Entity\QuizOption $options
     */
    public function addQuizOption(\Dodici\Fansworld\WebBundle\Entity\QuizOption $options)
    {
        $this->options[] = $options;
    }

    /**
     * Get options
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set quizquestion
     *
     * @param Dodici\Fansworld\WebBundle\Entity\QuizQuestion $quizquestion
     */
    public function setQuizquestion(\Dodici\Fansworld\WebBundle\Entity\QuizQuestion $quizquestion)
    {
        $this->quizquestion = $quizquestion;
    }

    /**
     * Get quizquestion
     *
     * @return Dodici\Fansworld\WebBundle\Entity\QuizQuestion 
     */
    public function getQuizquestion()
    {
        return $this->quizquestion;
    }
}