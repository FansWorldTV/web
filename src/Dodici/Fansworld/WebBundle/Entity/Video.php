<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Dodici\Fansworld\WebBundle\Model\VisitableInterface;
use Dodici\Fansworld\WebBundle\Model\SearchableInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\Video
 *
 * @ORM\Table(name="video")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\VideoRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Video implements SearchableInterface, VisitableInterface
{
    const WEIGHT_OUTDATE_FACTOR = 0.2;
    const WEIGHT_LIKES_FACTOR = 1.5;
    const WEIGHT_VIEWS_FACTOR = 1.0;
    const WEIGHT_HIGHLIGHT_FACTOR = 1.1;

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
     * @var Application\Sonata\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="author_id", referencedColumnName="id")
     * })
     */
    private $author;

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
     * @var datetime $createdAtWeek
     *
     * @ORM\Column(name="created_at_week", type="bigint", nullable=false)
     */
    private $createdAtWeek;

    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var boolean $processed
     *
     * @ORM\Column(name="processed", type="boolean", nullable=false)
     */
    private $processed;

    /**
     * @var boolean $notified
     *
     * @ORM\Column(name="notified", type="boolean", nullable=false)
     */
    private $notified;

    /**
     * @var integer $duration
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var string $stream
     *
     * @ORM\Column(name="stream", type="string", length=250, nullable=true)
     */
    private $stream;

    /**
     * @var string $youtube
     *
     * @ORM\Column(name="youtube", type="string", length=250, nullable=true)
     */
    private $youtube;

    /**
     * @var string $vimeo
     *
     * @ORM\Column(name="vimeo", type="string", length=250, nullable=true)
     */
    private $vimeo;

    /**
     * @var integer $privacy
     * Privacy::EVERYONE|Privacy::FRIENDS_ONLY
     *
     * @ORM\Column(name="privacy", type="integer", nullable=false)
     */
    private $privacy;

    /**
     * @var boolean $highlight
     *
     * @ORM\Column(name="highlight", type="boolean", nullable=false)
     */
    private $highlight;

    /**
     * @var integer $likeCount
     *
     * @ORM\Column(name="likecount", type="integer", nullable=false)
     */
    private $likeCount;

    /**
     * @var integer $processingTries
     *
     * @ORM\Column(name="processingtries", type="integer", nullable=false)
     */
    private $processingTries;

    /**
     * @var integer $commentCount
     *
     * @ORM\Column(name="commentcount", type="integer", nullable=false)
     */
    private $commentCount;

    /**
     * @var integer $viewCount
     *
     * @ORM\Column(name="viewcount", type="integer", nullable=false)
     */
    private $viewCount;

    /**
     * @Gedmo\Slug(fields={"title"}, unique=false)
     * @ORM\Column(length=250)
     */
    private $slug;

    /**
     * @var VideoCategory
     *
     * @Assert\NotNull()
     *
     * @ORM\ManyToOne(targetEntity="VideoCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="videocategory_id", referencedColumnName="id")
     * })
     */
    private $videocategory;

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
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     */
    private $image;

    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="splash_id", referencedColumnName="id")
     */
    private $splash;

    /**
     * @ORM\OneToMany(targetEntity="Liking", mappedBy="video", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $likings;

    /**
     * @ORM\OneToMany(targetEntity="HasTag", mappedBy="video", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hastags;

    /**
     * @ORM\OneToMany(targetEntity="HasUser", mappedBy="video", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasusers;

    /**
     * @ORM\OneToMany(targetEntity="HasIdol", mappedBy="video", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasidols;

    /**
     * @ORM\OneToMany(targetEntity="HasTeam", mappedBy="video", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasteams;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="video", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $comments;

    /**
     * @ORM\OneToMany(targetEntity="Visit", mappedBy="video", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $visits;

    /**
     * @var integer $visitCount
     *
     * @ORM\Column(name="visitcount", type="integer", nullable=false)
     */
    private $visitCount;

    /**
     * @var integer $weight
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight;

    /**
     * @var Genre
     *
     * @Assert\NotNull()
     *
     * @ORM\ManyToOne(targetEntity="Genre")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="genre_id", referencedColumnName="id")
     * })
     */
    private $genre;

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
    	if (null === $this->active) {
            $this->setActive(true);
        }
        if (null === $this->notified) {
            $this->setNotified(false);
        }
        if (null === $this->likeCount) {
        	$this->setLikeCount(0);
        }
        if (null === $this->commentCount) {
        	$this->setCommentCount(0);
        }
        if (null === $this->viewCount) {
        	$this->setViewCount(0);
        }
    	if (null === $this->highlight) {
        	$this->setHighlight(false);
        }
        if (null === $this->author) {
        	$this->setPrivacy(Privacy::EVERYONE);
        }

    	if ($this->getYoutube()) {
			$youtube = $this->getYoutube();
			$youtube = str_replace(
			array('http://','www.youtube.com/watch?v=','youtu.be/','www.youtube.com/v/'),
			array('','','',''),
			$youtube);
			if (strpos($youtube, '&') !== false) {
				$youtube = substr($youtube, 0, strpos($youtube, '&'));
			}
			$this->setYoutube($youtube);
		}

		if ($this->getVimeo()) {
		    $vimeo = $this->getVimeo();
			$vimeo = str_replace(
			array('http://','https://','www.','vimeo.com/'),
			array('','','',''),
			$vimeo);
			if (strpos($vimeo, '?') !== false) {
				$vimeo = substr($vimeo, 0, strpos($vimeo, '?'));
			}
			$this->setVimeo($vimeo);
		}
        if (null === $this->weight) {
        	$this->calculateWeight();
        }
    }

	/**
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->calculateWeight();
    }

    public function calculateWeight()
    {
    	$this->setWeight(
    	    round(
        	    (($this->visitCount ? log($this->visitCount * self::WEIGHT_VIEWS_FACTOR, 10) : 0) +
        	    ($this->likeCount ? log($this->likeCount * self::WEIGHT_LIKES_FACTOR, 10) : 0) +
        	    $this->createdAt->format('U') / 86400 * self::WEIGHT_OUTDATE_FACTOR)
        	    *
        	    ($this->highlight ? self::WEIGHT_HIGHLIGHT_FACTOR : 1)
    	    )
    	);
    }

    public function __construct()
    {
        $this->visits = new \Doctrine\Common\Collections\ArrayCollection();
        $this->visitCount = 0;
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->hasteams = new \Doctrine\Common\Collections\ArrayCollection();
        $this->hasidols = new \Doctrine\Common\Collections\ArrayCollection();
        $this->hastags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->hasusers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->processed = false;
        $this->privacy = Privacy::EVERYONE;
        $this->processingTries = 0;
    }

	public function likeUp()
    {
    	$this->setLikeCount($this->getLikeCount() + 1);
    }
    public function likeDown()
    {
    	if ($this->getLikeCount() > 0) {
    		$this->setLikeCount($this->getLikeCount() - 1);
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
        $this->setCreatedAtWeek($createdAt->format('U'));
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
     * Set createdAtWeek
     *
     * @param bigint $createdAtWeek
     */
    public function setCreatedAtWeek($createdAtWeek)
    {
        $this->createdAtWeek = $createdAtWeek;
    }

    /**
     * Get createdAtWeek
     *
     * @return bigint
     */
    public function getCreatedAtWeek()
    {
        return $this->createdAtWeek;
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
     * Set processed
     *
     * @param boolean $processed
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    /**
     * Get processed
     *
     * @return boolean
     */
    public function getProcessed()
    {
        return $this->processed;
    }

	/**
     * Set notified
     *
     * @param boolean $notified
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;
    }

    /**
     * Get notified
     *
     * @return boolean
     */
    public function getNotified()
    {
        return $this->notified;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set stream
     *
     * @param integer $stream
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
    }

    /**
     * Get stream
     *
     * @return integer
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Set youtube
     *
     * @param string $youtube
     */
    public function setYoutube($youtube)
    {
        $this->youtube = $youtube;
    }

    /**
     * Get youtube
     *
     * @return string
     */
    public function getYoutube()
    {
        return $this->youtube;
    }

	/**
     * Set vimeo
     *
     * @param string $vimeo
     */
    public function setVimeo($vimeo)
    {
        $this->vimeo = $vimeo;
    }

    /**
     * Get vimeo
     *
     * @return string
     */
    public function getVimeo()
    {
        return $this->vimeo;
    }

    /**
     * Set privacy
     *
     * @param integer $privacy
     */
    public function setPrivacy($privacy)
    {
        $this->privacy = $privacy;
    }

    /**
     * Get privacy
     *
     * @return integer
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

	/**
     * Set highlight
     *
     * @param boolean $highlight
     */
    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }

    /**
     * Get highlight
     *
     * @return boolean
     */
    public function getHighlight()
    {
        return $this->highlight;
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
     * Set videocategory
     *
     * @param Dodici\Fansworld\WebBundle\Entity\VideoCategory $videocategory
     */
    public function setVideocategory(\Dodici\Fansworld\WebBundle\Entity\VideoCategory $videocategory)
    {
        $this->videocategory = $videocategory;
    }

    /**
     * Get videocategory
     *
     * @return Dodici\Fansworld\WebBundle\Entity\VideoCategory
     */
    public function getVideocategory()
    {
        return $this->videocategory;
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

    /**
     * Set author
     *
     * @param Application\Sonata\UserBundle\Entity\User $author
     */
    public function setAuthor($author)
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
     * Add likings
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Liking $likings
     */
    public function addLiking(\Dodici\Fansworld\WebBundle\Entity\Liking $likings)
    {
        $this->likings[] = $likings;
    }

    /**
     * Get likings
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getLikings()
    {
        return $this->likings;
    }

    /**
     * Set likeCount
     *
     * @param integer $likeCount
     */
    public function setLikeCount($likeCount)
    {
        $this->likeCount = $likeCount;
    }

    /**
     * Get likeCount
     *
     * @return integer
     */
    public function getLikeCount()
    {
        return $this->likeCount;
    }

    /**
     * Set processingTries
     *
     * @param integer $processingTries
     */
    public function setProcessingTries($processingTries)
    {
        $this->processingTries = $processingTries;
    }

    /**
     * Get processingTries
     *
     * @return integer
     */
    public function getProcessingTries()
    {
        return $this->processingTries;
    }

    /**
     * Set viewCount
     *
     * @param integer $viewCount
     */
    public function setViewCount($viewCount)
    {
        $this->viewCount = $viewCount;
    }

    /**
     * Get viewCount
     *
     * @return integer
     */
    public function getViewCount()
    {
        return $this->viewCount;
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
        $hasteams->setVideo($this);
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
     * Add hasidols
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasIdol $hasidols
     */
    public function addHasIdol(\Dodici\Fansworld\WebBundle\Entity\HasIdol $hasidols)
    {
        $hasidols->setVideo($this);
        $this->hasidols[] = $hasidols;
    }

    public function addHasidols($hasidols){
        $this->addHasIdol($hasidols);
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

    /**
     *  Set hasidols
     */
    public function setHasidols($hasidols){
        foreach ($hasidols as $hasidol){
            $this->addHasIdol($hasidol);
        }
    }

	/**
     * Add visits
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Visit $visits
     */
    public function addVisit(\Dodici\Fansworld\WebBundle\Entity\Visit $visits)
    {
        $visits->setVideo($this);
        $this->setVisitCount($this->getVisitCount() + 1);
        $this->visits[] = $visits;
    }

	public function addVisits(\Dodici\Fansworld\WebBundle\Entity\Visit $visits)
    {
        $this->addVisit($visits);
    }

    /**
     * Get visits
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getVisits()
    {
        return $this->visits;
    }

	public function setVisits($visits)
    {
        $this->visits = $visits;
    }

    /**
     * Set visitCount
     *
     * @param integer $visitCount
     */
    public function setVisitCount($visitCount)
    {
        $this->visitCount = $visitCount;
    }

    /**
     * Get visitCount
     *
     * @return integer
     */
    public function getVisitCount()
    {
        return $this->visitCount;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

     /**
     * Set Genre
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Genre $genre
     */
    public function setGenre(\Dodici\Fansworld\WebBundle\Entity\Genre $genre)
    {
        $this->genre = $genre;
    }

    /**
     * Get Genre
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Genre
     */
    public function getGenre()
    {
        return $this->genre;
    }


    public function removeHas($item)
    {
        if ($item instanceof HasTeam) {
            $collection = &$this->hasteams;
        } elseif ($item instanceof HasIdol) {
            $collection = &$this->hasidols;
        } elseif ($item instanceof HasUser) {
            $collection = &$this->hasusers;
        } elseif ($item instanceof HasTag) {
            $collection = &$this->hastags;
        }

        foreach ($collection as $i => $colitem) {
            if ($colitem == $item) {
                $collection->remove($i);
                return true;
            }
        }
        return false;
    }

    /**
     * Get provider
     *
     * @return string ('kaltura' or 'youtube' or 'vimeo')
     */
    public function getProvider() {
        $provider = null;
        if ($this->getStream() != null) {
            $provider = "kaltura";
        } elseif ($this->getYoutube() != null) {
            $provider = "youtube";
        } elseif ($this->getVimeo() != null) {
            $provider = "vimeo";
        }
        return $provider;
    }
}