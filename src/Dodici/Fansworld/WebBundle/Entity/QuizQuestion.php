<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\QuizQuestion
 *
 * @ORM\Table(name="quizquestion")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\QuizQuestionRepository")
 */
class QuizQuestion implements Translatable
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
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=false)
     */
    private $title;

    /**
     * @var text $content
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;
        
    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
        
    /**
     * @var boolean $multiple
     *
     * @ORM\Column(name="multiple", type="boolean", nullable=false)
     */
    private $multiple;
    
    /**
     * @var boolean $results
     *
     * @ORM\Column(name="results", type="boolean", nullable=false)
     */
    private $results;
    
    /**
     * @var bigint $score
     *
     * @ORM\Column(name="score", type="bigint", nullable=true)
     */
    private $score;
    
    /**
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="Team")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="team_id", referencedColumnName="id")
     * })
     */
    private $team;
    
    /**
     * @var Idol
     *
     * @ORM\ManyToOne(targetEntity="Idol")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idol_id", referencedColumnName="id")
     * })
     */
    private $idol;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
        
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
     * @ORM\OneToMany(targetEntity="QuizOption", mappedBy="quizquestion", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $quizoptions;
    
    /**
     * @ORM\OneToMany(targetEntity="QuizAnswer", mappedBy="quizquestion", cascade={"remove"})
     */
    protected $quizanswers;

    public function __construct()
    {
        $this->quizoptions = new ArrayCollection();
        $this->quizanswers = new ArrayCollection();
        $this->multiple = false;
        $this->active = true;
        $this->results = true;
        $this->createdAt = new \DateTime();
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
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set multiple
     *
     * @param boolean $multiple
     */
    public function setMultiple($multiple)
    {
        $this->multiple = $multiple;
    }

    /**
     * Get multiple
     *
     * @return boolean 
     */
    public function getMultiple()
    {
        return $this->multiple;
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
     * Add quizoptions
     *
     * @param Dodici\WebBundle\Entity\QuizOption $quizoptions
     */
    public function addQuizOption(\Dodici\WebBundle\Entity\QuizOption $quizoptions)
    {
        $this->quizoptions[] = $quizoptions;
    }
    
	/**
     * Add quizoptions
     *
     * @param Dodici\WebBundle\Entity\QuizOption $quizoptions
     */
    public function addQuizOptions(\Dodici\WebBundle\Entity\QuizOption $quizoptions)
    {
        $this->quizoptions[] = $quizoptions;
    }

    /**
     * Get quizoptions
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getQuizoptions()
    {
        return $this->quizoptions;
    }
    
	public function setQuizoptions($qo)
    {
        $this->quizoptions = $qo;
    }

    /**
     * Add quizanswers
     *
     * @param Dodici\WebBundle\Entity\QuizAnswer $quizanswers
     */
    public function addQuizAnswer(\Dodici\WebBundle\Entity\QuizAnswer $quizanswers)
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
     * Set results
     *
     * @param boolean $results
     */
    public function setResults($results)
    {
        $this->results = $results;
    }

    /**
     * Get results
     *
     * @return boolean 
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Set score
     *
     * @param bigint $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * Get score
     *
     * @return bigint 
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set team
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Team $team
     */
    public function setTeam(\Dodici\Fansworld\WebBundle\Entity\Team $team)
    {
        $this->team = $team;
    }

    /**
     * Get team
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Team 
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set idol
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Idol $idol
     */
    public function setIdol(\Dodici\Fansworld\WebBundle\Entity\Idol $idol)
    {
        $this->idol = $idol;
    }

    /**
     * Get idol
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Idol 
     */
    public function getIdol()
    {
        return $this->idol;
    }
}