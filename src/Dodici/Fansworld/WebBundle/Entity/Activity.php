<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Activity
 * 
 * An activity, usually performed by a user
 *
 * @ORM\Table(name="activity")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\ActivityRepository")
 */
class Activity
{
    // User or fansworld uploaded a new video
    const TYPE_NEW_VIDEO = 1;
    // User uploaded a new photo
    const TYPE_NEW_PHOTO = 2;
    // User became a fan of another user, team or idol
    const TYPE_BECAME_FAN = 3;
    // User checked into event
    const TYPE_CHECKED_IN = 4;
    // User was labelled in a content
    const TYPE_LABELLED_IN = 5;
    // User liked a content
    const TYPE_LIKED = 6;
    // User shared something to his wall
    const TYPE_SHARED = 7;

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
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var integer $type
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;
    
    /**
     * @var Video
     *
     * @ORM\ManyToOne(targetEntity="Video")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="video_id", referencedColumnName="id")
     * })
     */
    private $video;
    
    /**
     * @var Photo
     *
     * @ORM\ManyToOne(targetEntity="Photo")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="photo_id", referencedColumnName="id")
     * })
     */
    private $photo;
    
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
     * @ORM\OneToMany(targetEntity="HasTag", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true", fetch="EAGER")
     */
    protected $hastags;

    /**
     * @ORM\OneToMany(targetEntity="HasUser", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true", fetch="EAGER")
     */
    protected $hasusers;

    /**
     * @ORM\OneToMany(targetEntity="HasTeam", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true", fetch="EAGER")
     */
    protected $hasteams;

    /**
     * @ORM\OneToMany(targetEntity="HasIdol", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true", fetch="EAGER")
     */
    protected $hasidols;

    public function __toString()
    {
        return $this->getId();
    }

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    public static function getTypeList()
    {
        return array(
            self::TYPE_NEW_VIDEO => 'new_video',
            self::TYPE_NEW_PHOTO => 'new_photo',
            self::TYPE_BECAME_FAN => 'became_fan',
            self::TYPE_CHECKED_IN => 'checked_in',
            self::TYPE_LABELLED_IN => 'labelled_in',
            self::TYPE_LIKED => 'liked',
            self::TYPE_SHARED => 'shared'
        );
    }

    public function getTypeName()
    {
        $arr = self::getTypeList();
        return $arr[$this->type];
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
     * Add hastags
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasTag $hastags
     */
    public function addHasTag(\Dodici\Fansworld\WebBundle\Entity\HasTag $hastags)
    {
        $hastags->setActivity($this);
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
        $hasusers->setActivity($this);
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
        $hasteams->setActivity($this);
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
     * Add hasidols
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasIdol $hasidols
     */
    public function addHasIdol(\Dodici\Fansworld\WebBundle\Entity\HasIdol $hasidols)
    {
        $hasidols->setActivity($this);
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
     * Set video
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Video $video
     */
    public function setVideo(\Dodici\Fansworld\WebBundle\Entity\Video $video)
    {
        $this->video = $video;
    }

    /**
     * Get video
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Video 
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set photo
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Photo $photo
     */
    public function setPhoto(\Dodici\Fansworld\WebBundle\Entity\Photo $photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get photo
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Photo 
     */
    public function getPhoto()
    {
        return $this->photo;
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
}