<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\Contest
 * 
 * A contest is created by FW so that users will participate and possibly become winners, either by FW's choice, or public vote
 *
 * @ORM\Table(name="contest")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\ContestRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Contest implements Translatable
{
    // No input other than clicking on a button are required to participate

    const TYPE_PARTICIPATE = 1;
    // A text/photo/video is uploaded along with the participation. Intended to be subjected to public vote
    const TYPE_TEXT = 2;
    const TYPE_PHOTO = 3;
    const TYPE_VIDEO = 4;

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
     * @var text $data
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

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
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var datetime $endDate
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var integer $type
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

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
     * @ORM\OneToMany(targetEntity="ContestParticipant", mappedBy="contest", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $participants;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="contest", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $comments;

    /**
     * @ORM\OneToMany(targetEntity="Liking", mappedBy="contest", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $likings;

    /**
     * @ORM\OneToMany(targetEntity="HasTag", mappedBy="contest", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hastags;

    /**
     * @ORM\OneToMany(targetEntity="HasUser", mappedBy="contest", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasusers;

    /**
     * @ORM\OneToMany(targetEntity="HasIdol", mappedBy="contest", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasidols;

    /**
     * @ORM\OneToMany(targetEntity="HasTeam", mappedBy="contest", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasteams;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->comments = new ArrayCollection();
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
     * Set data
     *
     * @param text $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return text 
     */
    public function getData()
    {
        return $this->data;
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
     * Add participants
     *
     * @param Dodici\Fansworld\WebBundle\Entity\ContestParticipant $participants
     */
    public function addContestParticipant(\Dodici\Fansworld\WebBundle\Entity\ContestParticipant $participants)
    {
        $this->participants[] = $participants;
    }

    /**
     * Get participants
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    public function setParticipants($participants)
    {
        $this->participants = $participants;
    }

    public function addParticipants($participant)
    {
        $this->participants[] = $participant;
    }

    /**
     * Set endDate
     *
     * @param datetime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * Get endDate
     *
     * @return datetime 
     */
    public function getEndDate()
    {
        return $this->endDate;
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