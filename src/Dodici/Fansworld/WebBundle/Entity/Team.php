<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Dodici\Fansworld\WebBundle\Model\SearchableInterface;

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
class Team implements Translatable, SearchableInterface
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
     * @var string $shortname
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="shortname", type="string", length=100, nullable=true)
     */
    private $shortname;
    
    /**
     * @var string $letters
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="letters", type="string", length=100, nullable=true)
     */
    private $letters;
    
    /**
     * @var string $stadium
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="stadium", type="string", length=100, nullable=true)
     */
    private $stadium;
    
    /**
     * @var string $website
     *
     * @ORM\Column(name="website", type="string", length=100, nullable=true)
     */
    private $website;
    
    /**
     * @var text $content
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;
    
    /**
     * @var text $nicknames
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="nicknames", type="text", nullable=true)
     */
    private $nicknames;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
    /**
     * @var datetime $foundedAt
     *
     * @ORM\Column(name="founded_at", type="datetime", nullable=true)
     */
    private $foundedAt;
    
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
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="splash", referencedColumnName="id")
     */
    private $splash;
    
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
     * @var string $external
     *
     * @ORM\Column(name="external", type="string", length=100, nullable=true)
     */
    private $external;
    
    /**
     * @var string $twitter
     *
     * @ORM\Column(name="twitter", type="string", length=100, nullable=true)
     */
    private $twitter;
    
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
    
    public function __toString()
    {
    	return $this->getTitle();
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
     * Set twitter
     *
     * @param string $twitter
     */
    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
    }

    /**
     * Get twitter
     *
     * @return string 
     */
    public function getTwitter()
    {
        return $this->twitter;
    }

    /**
     * Set shortname
     *
     * @param string $shortname
     */
    public function setShortname($shortname)
    {
        $this->shortname = $shortname;
    }

    /**
     * Get shortname
     *
     * @return string 
     */
    public function getShortname()
    {
        return $this->shortname;
    }

    /**
     * Set letters
     *
     * @param string $letters
     */
    public function setLetters($letters)
    {
        $this->letters = $letters;
    }

    /**
     * Get letters
     *
     * @return string 
     */
    public function getLetters()
    {
        return $this->letters;
    }

    /**
     * Set stadium
     *
     * @param string $stadium
     */
    public function setStadium($stadium)
    {
        $this->stadium = $stadium;
    }

    /**
     * Get stadium
     *
     * @return string 
     */
    public function getStadium()
    {
        return $this->stadium;
    }

    /**
     * Set website
     *
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * Get website
     *
     * @return string 
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set nicknames
     *
     * @param text $nicknames
     */
    public function setNicknames($nicknames)
    {
        $this->nicknames = $nicknames;
    }

    /**
     * Get nicknames
     *
     * @return text 
     */
    public function getNicknames()
    {
        return $this->nicknames;
    }

    /**
     * Set foundedAt
     *
     * @param datetime $foundedAt
     */
    public function setFoundedAt($foundedAt)
    {
        $this->foundedAt = $foundedAt;
    }

    /**
     * Get foundedAt
     *
     * @return datetime 
     */
    public function getFoundedAt()
    {
        return $this->foundedAt;
    }

    /**
     * Set splash
     *
     * @param Application\Sonata\MediaBundle\Entity\Media $splash
     */
    public function setSplash(\Application\Sonata\MediaBundle\Entity\Media $splash)
    {
        $this->splash = $splash;
    }

    /**
     * Get splash
     *
     * @return Application\Sonata\MediaBundle\Entity\Media 
     */
    public function getSplash()
    {
        return $this->splash;
    }
}