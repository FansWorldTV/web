<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Dodici\Fansworld\WebBundle\Entity\Album
 * 
 * A named collection of Photos owned by a User
 * Can be tagged
 *
 * @ORM\Table(name="album")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\AlbumRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Album
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
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
    
    /**
     * @var integer $privacy
     * Privacy::EVERYONE|Privacy::FRIENDS_ONLY
     *
     * @ORM\Column(name="privacy", type="integer", nullable=false)
     */
    private $privacy;
    
    /**
     * @var integer $likeCount
     *
     * @ORM\Column(name="likecount", type="integer", nullable=false)
     */
    private $likeCount;
    
    /**
     * @var integer $commentCount
     *
     * @ORM\Column(name="commentcount", type="integer", nullable=false)
     */
    private $commentCount;
    
    /**
     * @var integer $photoCount
     *
     * @ORM\Column(name="photocount", type="integer", nullable=false)
     */
    private $photoCount;
    
    /**
     * @Gedmo\Slug(fields={"title"}, unique=false)
     * @ORM\Column(length=250)
     */
    private $slug;
    
    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="image", referencedColumnName="id")
     */
    private $image;
    
    /**
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="album", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $photos;
    
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="album", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $comments;
    
    /**
     * @ORM\OneToMany(targetEntity="Liking", mappedBy="album", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $likings;
    
    /**
     * @ORM\OneToMany(targetEntity="HasTag", mappedBy="album", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hastags;
    
    /**
     * @ORM\OneToMany(targetEntity="HasTeam", mappedBy="album", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasteams;
    
    /**
     * @ORM\OneToMany(targetEntity="HasUser", mappedBy="album", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasusers;
    
    public function __construct()
    {
        $this->photos = new \Doctrine\Common\Collections\ArrayCollection();
    	$this->comments = new \Doctrine\Common\Collections\ArrayCollection();
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
        if (null === $this->likeCount) {
        	$this->setLikeCount(0);
        }
        if (null === $this->commentCount) {
        	$this->setCommentCount(0);
        }
    	if (null === $this->photoCount) {
        	$this->setPhotoCount(0);
        }
        if (null === $this->active) {
        	$this->setActive(true);
        }
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
     * Add photos
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Photo $photos
     */
    public function addPhoto(\Dodici\Fansworld\WebBundle\Entity\Photo $photos)
    {
        $this->photos[] = $photos;
    }
    
	public function addPhotos(\Dodici\Fansworld\WebBundle\Entity\Photo $photos)
    {
        $this->photos[] = $photos;
    }

    /**
     * Get photos
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getPhotos()
    {
        return $this->photos;
    }
    
	public function setPhotos($photos)
    {
        $this->photos = $photos;
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
     * Set photoCount
     *
     * @param integer $photoCount
     */
    public function setPhotoCount($photoCount)
    {
        $this->photoCount = $photoCount;
    }

    /**
     * Get photoCount
     *
     * @return integer 
     */
    public function getPhotoCount()
    {
        return $this->photoCount;
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
}