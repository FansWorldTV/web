<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\VideoCategory
 *
 * @ORM\Table(name="team")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\TeamRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Team implements Translatable
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
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="image", referencedColumnName="id")
     */
    private $image;
    
    /**
     * @var TeamCategory
     *
     * @ORM\ManyToOne(targetEntity="TeamCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="teamcategory_id", referencedColumnName="id")
     * })
     */
    private $teamcategory;
    
    /**
     * @Gedmo\Slug(fields={"title"}, unique=false)
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    private $slug;
    
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
     * @ORM\OneToMany(targetEntity="Application\Sonata\UserBundle\Entity\User", mappedBy="team")
     */
    protected $idols;
    
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="team", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $comments;

    /**
     * @var integer $fanCount
     * @ORM\Column(name="fancount", type="bigint", nullable=false)
     */
    private $fanCount;
    
	/**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if (null === $this->createdAt) {
            $this->setCreatedAt(new \DateTime());
        }
    	if (null === $this->active) {
            $this->setActive(true);
        }
        if (null === $this->fanCount) {
        	$this->setFanCount(0);
        }
    }
    public function __construct()
    {
        $this->idols = new \Doctrine\Common\Collections\ArrayCollection();
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set image
     *
     * @param Application\Sonata\MediaBundle\Entity\Media $image
     */
    public function setImage(\Application\Sonata\MediaBundle\Entity\Media $image)
    {
        $this->image = $image;
    }

    /**
     * Get image
     *
     * @return Application\Sonata\MediaBundle\Entity\Media 
     */
    public function getImage()
    {
        return $this->image;
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
     * Set teamcategory
     *
     * @param Dodici\Fansworld\WebBundle\Entity\TeamCategory $teamcategory
     */
    public function setTeamcategory(\Dodici\Fansworld\WebBundle\Entity\TeamCategory $teamcategory)
    {
        $this->teamcategory = $teamcategory;
    }

    /**
     * Get teamcategory
     *
     * @return Dodici\Fansworld\WebBundle\Entity\TeamCategory 
     */
    public function getTeamcategory()
    {
        return $this->teamcategory;
    }

    /**
     * Add idols
     *
     * @param Application\Sonata\UserBundle\Entity\User $idols
     */
    public function addIdol(\Dodici\Fansworld\WebBundle\Entity\Idol $idols)
    {
        $this->idols[] = $idols;
    }

    /**
     * Get idols
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getIdols()
    {
        return $this->idols;
    }

    /**
     * Set fanCount
     *
     * @param bigint $fanCount
     */
    public function setFanCount($fanCount)
    {
        $this->fanCount = $fanCount;
    }

    /**
     * Get fanCount
     *
     * @return bigint 
     */
    public function getFanCount()
    {
        return $this->fanCount;
    }

    /**
     * Add idols
     *
     * @param Application\Sonata\UserBundle\Entity\User $idols
     */
    public function addUser(\Application\Sonata\UserBundle\Entity\User $idols)
    {
        $this->idols[] = $idols;
    }
}