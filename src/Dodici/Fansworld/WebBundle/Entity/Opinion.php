<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Dodici\Fansworld\WebBundle\Entity\Opinion
 *
 * @ORM\Table(name="opinion")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\OpinionRepository")
 */
class Opinion
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
     * @var text $content
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;
        
    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
    
    /**
     * @var boolean $staff
     *
     * @ORM\Column(name="staff", type="boolean", nullable=false)
     */
    private $staff;
    
    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     * })
     */
    private $event;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
	/**
     * @ORM\OneToMany(targetEntity="OpinionVote", mappedBy="opinion", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $opinionvotes;
    
    /**
     * @var integer $yesCount
     *
     * @ORM\Column(name="yescount", type="integer", nullable=false)
     */
    private $yesCount;
    
    /**
     * @var integer $noCount
     *
     * @ORM\Column(name="nocount", type="integer", nullable=false)
     */
    private $noCount;

    public function __construct()
    {
        $this->opinionvotes = new ArrayCollection();
        $this->active = true;
        $this->staff = false;
        $this->createdAt = new \DateTime();
        $this->yesCount = 0;
        $this->noCount = 0;
    }

    public function __toString()
    {
    	return $this->getContent();
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
     * Set staff
     *
     * @param boolean $staff
     */
    public function setStaff($staff)
    {
        $this->staff = $staff;
    }

    /**
     * Get staff
     *
     * @return boolean 
     */
    public function getStaff()
    {
        return $this->staff;
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
     * Set yesCount
     *
     * @param integer $yesCount
     */
    public function setYesCount($yesCount)
    {
        $this->yesCount = $yesCount;
    }

    /**
     * Get yesCount
     *
     * @return integer 
     */
    public function getYesCount()
    {
        return $this->yesCount;
    }

    /**
     * Set noCount
     *
     * @param integer $noCount
     */
    public function setNoCount($noCount)
    {
        $this->noCount = $noCount;
    }

    /**
     * Get noCount
     *
     * @return integer 
     */
    public function getNoCount()
    {
        return $this->noCount;
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
     * Set event
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Event $event
     */
    public function setEvent(\Dodici\Fansworld\WebBundle\Entity\Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get event
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Event 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Add opinionvotes
     *
     * @param Dodici\Fansworld\WebBundle\Entity\OpinionVote $opinionvotes
     */
    public function addOpinionVote(\Dodici\Fansworld\WebBundle\Entity\OpinionVote $opinionvotes)
    {
        $this->opinionvotes[] = $opinionvotes;
    }

    /**
     * Get opinionvotes
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getOpinionvotes()
    {
        return $this->opinionvotes;
    }
}