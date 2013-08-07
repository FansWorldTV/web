<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\QuizOption
 *
 * @ORM\Table(name="quizoption")
 * @ORM\Entity
 */
class QuizOption //implements Translatable
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
     * @var string $title
     * Gedmo\Translatable
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=false)
     */
    private $title;

    /**
     * @var integer $number
     *
     * @ORM\Column(name="number", type="integer", nullable=true)
     */
    private $number;
    
    /**
     * @var boolean $correct
     *
     * @ORM\Column(name="correct", type="boolean", nullable=false)
     */
    private $correct;
    
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
	 * @Gedmo\Locale
	 * Used locale to override Translation listener`s locale
	 * this is not a mapped field of entity metadata, just a simple property
	 */
	private $locale;
	
	public function setTranslatableLocale($locale)
	{
	    $this->locale = $locale;
	}


	/**
     * @ORM\OneToMany(targetEntity="QuizAnswer", mappedBy="quizoption")
     */
    protected $quizanswers;

    public function __construct()
    {
        $this->quizanswers = new ArrayCollection();
        $this->correct = false;
    }

    public function __toString()
    {
    	return $this->getTitle();
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
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set number
     *
     * @param integer $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get number
     *
     * @return integer 
     */
    public function getNumber()
    {
        return $this->number;
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

    /**
     * Add quizanswers
     *
     * @param Dodici\Fansworld\WebBundle\Entity\QuizAnswer $quizanswers
     */
    public function addQuizAnswer(\Dodici\Fansworld\WebBundle\Entity\QuizAnswer $quizanswers)
    {
        $this->quizanswers[] = $quizanswers;
    }

    /**
     * Get quizanswers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getQuizanswers()
    {
        return $this->quizanswers;
    }

    /**
     * Set correct
     *
     * @param boolean $correct
     */
    public function setCorrect($correct)
    {
        $this->correct = $correct;
    }

    /**
     * Get correct
     *
     * @return boolean 
     */
    public function getCorrect()
    {
        return $this->correct;
    }
}