<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Comment
 * 
 * A comment that can belong to several entities (including a user - target), and can be in response to another comment
 * Types other than TYPE_COMMENT are meant to be seen as an activity feed
 *
 * @ORM\Table(name="comment")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\CommentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Comment
{
    // Normal comment
    const TYPE_COMMENT = 1;
    // X has shared content Y
    const TYPE_SHARE = 4;
    
    // X and Y are now friends
    const TYPE_NEW_FRIEND = 2; // DEPRECATED
    // X has been labelled in content Y
    const TYPE_LABELLED = 3; // DEPRECATED
    // X likes content Y
    const TYPE_LIKES = 5; // DEPRECATED
    // X (idol) has a new forumthread
    const TYPE_NEW_THREAD = 6; // DEPRECATED
    // X (idol) answered in a thread. No longer in use
    const TYPE_THREAD_ANSWERED = 7; // DEPRECATED
    // X has uploaded a new photo
    const TYPE_NEW_PHOTO = 8; // DEPRECATED
    // X has uploaded a new video
    const TYPE_NEW_VIDEO = 9; // DEPRECATED

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
     * @ORM\OneToOne(targetEntity="Share", cascade={"remove", "persist"}, orphanRemoval="true")
     * @ORM\JoinColumn(name="share_id", referencedColumnName="id")
     */
    private $share;

    /**
     * @var NewsPost
     *
     * @ORM\ManyToOne(targetEntity="NewsPost")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="newspost_id", referencedColumnName="id")
     * })
     */
    private $newspost;

    /**
     * @var Application\Sonata\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="target_id", referencedColumnName="id")
     * })
     */
    private $target;

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
     * @var Album
     *
     * @ORM\ManyToOne(targetEntity="Album")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="album_id", referencedColumnName="id")
     * })
     */
    private $album;

    /**
     * @var Interest
     *
     * @ORM\ManyToOne(targetEntity="Interest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="interest_id", referencedColumnName="id")
     * })
     */
    private $interest;

    /**
     * @var Contest
     *
     * @ORM\ManyToOne(targetEntity="Contest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contest_id", referencedColumnName="id")
     * })
     */
    private $contest;

    /**
     * @var Proposal
     *
     * @ORM\ManyToOne(targetEntity="Proposal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="proposal_id", referencedColumnName="id")
     * })
     */
    private $proposal;

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
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     * })
     */
    private $event;

    /**
     * @var Meeting
     *
     * @ORM\ManyToOne(targetEntity="Meeting")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="meeting_id", referencedColumnName="id")
     * })
     */
    private $meeting;

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
     * @var Comment
     *
     * @ORM\ManyToOne(targetEntity="Comment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $comment;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $comments;

    /**
     * @ORM\OneToMany(targetEntity="Liking", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $likings;

    /**
     * @ORM\OneToMany(targetEntity="HasTag", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hastags;

    /**
     * @ORM\OneToMany(targetEntity="HasUser", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasusers;

    /**
     * @ORM\OneToMany(targetEntity="HasTeam", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasteams;

    /**
     * @ORM\OneToMany(targetEntity="HasIdol", mappedBy="comment", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasidols;

    public function __toString()
    {
        return (string) $this->getContent();
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
        if (null === $this->type) {
            $this->setType(self::TYPE_COMMENT);
        }
        if (null === $this->active) {
            $this->setActive(true);
        }
        if (null === $this->privacy) {
            $this->setPrivacy(Privacy::EVERYONE);
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

    public static function getTypeList()
    {
        return array(
            self::TYPE_COMMENT => 'comment',
            self::TYPE_NEW_FRIEND => 'new_friend',
            self::TYPE_LABELLED => 'user_tagged',
            self::TYPE_SHARE => 'share',
            self::TYPE_LIKES => 'likes',
            self::TYPE_NEW_THREAD => 'forum_created',
            self::TYPE_THREAD_ANSWERED => 'forum_answered',
            self::TYPE_NEW_PHOTO => 'new_photo',
            self::TYPE_NEW_VIDEO => 'new_video'
        );
    }

    public function getTypeName()
    {
        if ($this->comment) {
            return 'subcomment';
        } else {
            $arr = self::getTypeList();
            return $arr[$this->type];
        }
    }

    /**
     * Get content, truncated to length
     *
     * @return text
     */
    public function getSlimContent($length = 30)
    {
        $cont = $this->content;
        if (mb_strlen($cont) > $length) {
            $cont = substr($cont, 0, $length) . '...';
        }
        if (is_null($cont) || $cont === '')
            $cont = 'empty';
        return $cont;
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
     * Set newspost
     *
     * @param Dodici\Fansworld\WebBundle\Entity\NewsPost $newspost
     */
    public function setNewspost(\Dodici\Fansworld\WebBundle\Entity\NewsPost $newspost)
    {
        $this->newspost = $newspost;
    }

    /**
     * Get newspost
     *
     * @return Dodici\Fansworld\WebBundle\Entity\NewsPost 
     */
    public function getNewspost()
    {
        return $this->newspost;
    }

    /**
     * Set target
     *
     * @param Application\Sonata\UserBundle\Entity\User $target
     */
    public function setTarget(\Application\Sonata\UserBundle\Entity\User $target)
    {
        $this->target = $target;
    }

    /**
     * Get target
     *
     * @return Application\Sonata\UserBundle\Entity\User 
     */
    public function getTarget()
    {
        return $this->target;
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
     * Set album
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Album $album
     */
    public function setAlbum(\Dodici\Fansworld\WebBundle\Entity\Album $album)
    {
        $this->album = $album;
    }

    /**
     * Get album
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Album 
     */
    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * Set interest
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Interest $interest
     */
    public function setInterest(\Dodici\Fansworld\WebBundle\Entity\Interest $interest)
    {
        $this->interest = $interest;
    }

    /**
     * Get interest
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Interest 
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * Set contest
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Contest $contest
     */
    public function setContest(\Dodici\Fansworld\WebBundle\Entity\Contest $contest)
    {
        $this->contest = $contest;
    }

    /**
     * Get contest
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Contest 
     */
    public function getContest()
    {
        return $this->contest;
    }

    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->likings = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set comment
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Comment $comment
     */
    public function setComment(\Dodici\Fansworld\WebBundle\Entity\Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get comment
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Comment 
     */
    public function getComment()
    {
        return $this->comment;
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

    /**
     * Get comments
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getComments()
    {
        return $this->comments;
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
     * Set share
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Share $share
     */
    public function setShare(\Dodici\Fansworld\WebBundle\Entity\Share $share)
    {
        $this->share = $share;
    }

    /**
     * Get share
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Share 
     */
    public function getShare()
    {
        return $this->share;
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
     * Set meeting
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Meeting $meeting
     */
    public function setMeeting(\Dodici\Fansworld\WebBundle\Entity\Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    /**
     * Get meeting
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Meeting 
     */
    public function getMeeting()
    {
        return $this->meeting;
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

    /**
     * Set proposal
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Proposal $proposal
     */
    public function setProposal(\Dodici\Fansworld\WebBundle\Entity\Proposal $proposal)
    {
        $this->proposal = $proposal;
    }

    /**
     * Get proposal
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Proposal 
     */
    public function getProposal()
    {
        return $this->proposal;
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
}