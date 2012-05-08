<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Share
 * 
 * A sharing of a content by a user. Refactor a content superclass some day.
 *
 * @ORM\Table(name="share")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\ShareRepository")
 */
class Share
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
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $author;
    
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