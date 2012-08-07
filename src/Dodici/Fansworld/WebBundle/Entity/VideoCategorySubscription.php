<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\VideoCategorySubscription
 * 
 * A user's subscription to a VideoCategory
 *
 * @ORM\Table(name="videocategorysubscription")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\VideoCategorySubscriptionRepository")
 */
class VideoCategorySubscription
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
     *   @ORM\JoinColumn(name="author_id", referencedColumnName="id")
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
     * @var VideoCategory
     *
     * @ORM\ManyToOne(targetEntity="VideoCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="videocategory_id", referencedColumnName="id")
     * })
     */
    private $videocategory;
    
    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
}