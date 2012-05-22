<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Dodici\Fansworld\WebBundle\Model\SearchableInterface;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Dodici\Fansworld\WebBundle\Entity\Event
 * 
 * A live event to be followed by users, drawn from an external source - e.g. a soccer match people check into and comment about it
 *
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\EventRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Event implements SearchableInterface
{
    const TYPE_MATCH = 1;
    
    public static function getTypes()
    {
    	return array(
    		self::TYPE_MATCH => 'Partido'
    	);
    }
    
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
     * @var datetime $fromtime
     *
     * @ORM\Column(name="fromtime", type="datetime", nullable=true)
     */
    private $fromtime;
    
    /**
     * @var datetime $totime
     *
     * @ORM\Column(name="totime", type="datetime", nullable=true)
     */
    private $totime;
    
    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
    
    /**
     * @var integer $type
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;
    
    /**
     * @var string $external
     *
     * @ORM\Column(name="external", type="string", length=100, nullable=true)
     */
    private $external;
        
    /**
     * @var integer $userCount
     *
     * @ORM\Column(name="usercount", type="integer", nullable=false)
     */
    private $userCount;
    
    /**
     * @var integer $commentCount
     *
     * @ORM\Column(name="commentcount", type="integer", nullable=false)
     */
    private $commentCount;
    
    /**
     * @Gedmo\Slug(fields={"title"}, unique=false)
     * @ORM\Column(length=250)
     */
    private $slug;
        
    /**
     * @ORM\OneToMany(targetEntity="HasTag", mappedBy="event", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hastags;
    
    /**
     * @ORM\OneToMany(targetEntity="HasUser", mappedBy="event", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasusers;
    
    /**
     * @ORM\OneToMany(targetEntity="HasTeam", mappedBy="event", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasteams;
    
    /**
     * @ORM\OneToMany(targetEntity="HasIdol", mappedBy="event", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasidols;
    
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="event", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $comments;
    
    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setActive(true);
        $this->setType(self::TYPE_MATCH);
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
        if (null === $this->userCount) {
        	$this->setUserCount(0);
        }
        if (null === $this->commentCount) {
        	$this->setCommentCount(0);
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
     * Set commentCount
     *
     * @param integer $commentCount
     */
    public function setCommentCount($commentCount)
    {
        $this->commentCount = $commentCount;
    }

    /**
     * Get commentCount
     *
     * @return integer 
     */
    public function getCommentCount()
    {
        return $this->commentCount;
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
     * Set fromtime
     *
     * @param datetime $fromtime
     */
    public function setFromtime($fromtime)
    {
        $this->fromtime = $fromtime;
    }

    /**
     * Get fromtime
     *
     * @return datetime 
     */
    public function getFromtime()
    {
        return $this->fromtime;
    }

    /**
     * Set totime
     *
     * @param datetime $totime
     */
    public function setTotime($totime)
    {
        $this->totime = $totime;
    }

    /**
     * Get totime
     *
     * @return datetime 
     */
    public function getTotime()
    {
        return $this->totime;
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
     * Set type
     *
     * @param integer $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set external
     *
     * @param string $external
     */
    public function setExternal($external)
    {
        $this->external = $external;
    }

    /**
     * Get external
     *
     * @return string 
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * Set userCount
     *
     * @param integer $userCount
     */
    public function setUserCount($userCount)
    {
        $this->userCount = $userCount;
    }

    /**
     * Get userCount
     *
     * @return integer 
     */
    public function getUserCount()
    {
        return $this->userCount;
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