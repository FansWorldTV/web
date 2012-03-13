<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Notification
 *
 * @ORM\Table(name="notification")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\NotificationRepository")
 */
class Notification
{
    const TYPE_FRIENDSHIP_ACCEPTED = 1;
    const TYPE_USER_TAGGED = 2;
    const TYPE_COMMENT_ANSWERED = 3;
    const TYPE_FORUM_CREATED = 4;
    const TYPE_FORUM_ANSWERED = 5;
    
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
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
    
    /**
     * @var boolean $readed
     *
     * @ORM\Column(name="readed", type="boolean", nullable=false)
     */
    private $readed;
    
    /**
     * @var integer $type
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;
            
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
     * @var Contest
     *
     * @ORM\ManyToOne(targetEntity="Contest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contest_id", referencedColumnName="id")
     * })
     */
    private $contest;
    
    /**
     * @var Comment
     *
     * @ORM\ManyToOne(targetEntity="Comment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="comment_id", referencedColumnName="id")
     * })
     */
    private $comment;
    
    /**
     * @var ForumThread
     *
     * @ORM\ManyToOne(targetEntity="ForumThread")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="forumthread_id", referencedColumnName="id")
     * })
     */
    private $forumthread;
    
    public static function getTypeList() {
    	return array(
    		self::TYPE_COMMENT_ANSWERED => 'Respuesta a Comentario',
    		self::TYPE_FORUM_ANSWERED => 'Respuesta de ídolo en Foro',
    		self::TYPE_FORUM_CREATED => 'Un ídolo creó un foro',
    		self::TYPE_FRIENDSHIP_ACCEPTED => 'Solicitud de amistad aceptada',
    		self::TYPE_USER_TAGGED => 'Usuario etiquetado',
    	);
    }
    
    public function __construct() {
    	$this->createdAt = new \DateTime();
    	$this->active = true;
    	$this->readed = false;
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
     * Set readed
     *
     * @param boolean $readed
     */
    public function setReaded($readed)
    {
        $this->readed = $readed;
    }

    /**
     * Get readed
     *
     * @return boolean 
     */
    public function getReaded()
    {
        return $this->readed;
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
     * Set forumthread
     *
     * @param Dodici\Fansworld\WebBundle\Entity\ForumThread $forumthread
     */
    public function setForumthread(\Dodici\Fansworld\WebBundle\Entity\ForumThread $forumthread)
    {
        $this->forumthread = $forumthread;
    }

    /**
     * Get forumthread
     *
     * @return Dodici\Fansworld\WebBundle\Entity\ForumThread 
     */
    public function getForumthread()
    {
        return $this->forumthread;
    }
}