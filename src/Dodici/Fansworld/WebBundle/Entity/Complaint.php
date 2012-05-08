<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Complaint
 * 
 * A user-lodged complaint about a photo/video/comment/user(target)
 *
 * @ORM\Table(name="complaint")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\ComplaintRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Complaint
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
     * @var ComplaintCategory
     *
     * @ORM\ManyToOne(targetEntity="ComplaintCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="complaintcategory_id", referencedColumnName="id")
     * })
     */
    private $complaintcategory;
            
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
     * @var Comment
     *
     * @ORM\ManyToOne(targetEntity="Comment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="comment_id", referencedColumnName="id")
     * })
     */
    private $comment;
    
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
     * Set complaintcategory
     *
     * @param Dodici\Fansworld\WebBundle\Entity\ComplaintCategory $complaintcategory
     */
    public function setComplaintcategory(\Dodici\Fansworld\WebBundle\Entity\ComplaintCategory $complaintcategory)
    {
        $this->complaintcategory = $complaintcategory;
    }

    /**
     * Get complaintcategory
     *
     * @return Dodici\Fansworld\WebBundle\Entity\ComplaintCategory 
     */
    public function getComplaintcategory()
    {
        return $this->complaintcategory;
    }
}