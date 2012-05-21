<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Dodici\Fansworld\WebBundle\Entity\Meeting
 * 
 * A user-created planned gathering/event
 *
 * @ORM\Table(name="meeting")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Meeting
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
     *   @ORM\JoinColumn(name="author_id", referencedColumnName="id")
     * })
     */
    private $author;
    
    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=false)
     */
    private $title;
    
    /**
     * @var text $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
        
    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
    
        
    /**
     * @Gedmo\Slug(fields={"title"}, unique=false)
     * @ORM\Column(length=250)
     */
    private $slug;
        
    /**
     * @ORM\OneToMany(targetEntity="HasTag", mappedBy="meeting", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hastags;
    
    /**
     * @ORM\OneToMany(targetEntity="HasUser", mappedBy="meeting", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasusers;
    
    /**
     * @ORM\OneToMany(targetEntity="HasTeam", mappedBy="meeting", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasteams;

    /**
     * @ORM\OneToMany(targetEntity="HasIdol", mappedBy="meeting", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasidols;
    
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="meeting", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $comments;
    
    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setActive(true);
    }
    
	public function __toString()
    {
        return $this->getTitle();
    }
	
	/**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if (null === $this->createdAt) {
            $this->setCreatedAt(new \DateTime());
        }
    }
    

    /**
     * Add comments
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Comment $comments
     */
    public function addComment(\Dodici\Fansworld\WebBundle\Entity\Comment $comments)
    {
        $this->comments[] = $comments;
    }
	public function addComments(\Dodici\Fansworld\WebBundle\Entity\Comment $comments)
    {
        $this->comments[] = $comments;
    }

    /**
     * Get comments
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getComments()
    {
        return $this->comments;
    }
    
	public function setComments($comments)
    {
        $this->comments = $comments;
    }
    
    /**
     * Add hastags
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasTag $hastags
     */
    public function addHasTag(\Dodici\Fansworld\WebBundle\Entity\HasTag $hastags)
    {
        $this->hastags[] = $hastags;
    }

    /**
     * Get hastags
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getHastags()
    {
        return $this->hastags;
    }

    /**
     * Add hasusers
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasUser $hasusers
     */
    public function addHasUser(\Dodici\Fansworld\WebBundle\Entity\HasUser $hasusers)
    {
        $this->hasusers[] = $hasusers;
    }

    /**
     * Get hasusers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getHasusers()
    {
        return $this->hasusers;
    }
    
	/**
     * Admin methods
     */
    public function setHastags($hastags)
    {
        $this->hastags = $hastags;
    }
	public function addHastags($hastags)
    {
        $this->addHasTag($hastags);
    }
    
	public function setHasusers($hasusers)
    {
        $this->hasusers = $hasusers;
    }
	public function addHasusers($hasusers)
    {
        $this->addHasUser($hasusers);
    }

	/**
     * Add hasteams
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasTeam $hasteams
     */
    public function addHasTeam(\Dodici\Fansworld\WebBundle\Entity\HasTeam $hasteams)
    {
        $this->hasteams[] = $hasteams;
    }

    /**
     * Get hasteams
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getHasteams()
    {
        return $this->hasteams;
    }
    
	public function setHasteams($hasteams)
    {
        $this->hasteams = $hasteams;
    }
	public function addHasteams($hasteams)
    {
        $this->addHasTeam($hasteams);
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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
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
     * Add hasidols
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasIdol $hasidols
     */
    public function addHasIdol(\Dodici\Fansworld\WebBundle\Entity\HasIdol $hasidols)
    {
        $this->hasidols[] = $hasidols;
    }

    /**
     * Get hasidols
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getHasidols()
    {
        return $this->hasidols;
    }
}