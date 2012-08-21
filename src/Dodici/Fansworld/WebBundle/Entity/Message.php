<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Message
 * 
 * A private message to one or many users, may include shared content
 *
 * @ORM\Table(name="message")
 * @ORM\Entity
 */
class Message
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
	 * @ORM\OneToOne(targetEntity="Share", cascade={"remove", "persist"}, orphanRemoval="true")
	 * @ORM\JoinColumn(name="share_id", referencedColumnName="id")
	 */
    private $share;
    
    /**
     * @ORM\OneToMany(targetEntity="MessageTarget", mappedBy="message", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $messagetargets;
    
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
     * Add messagetargets
     *
     * @param Dodici\Fansworld\WebBundle\Entity\MessageTarget $messagetargets
     */
    public function addMessageTarget(\Dodici\Fansworld\WebBundle\Entity\MessageTarget $messagetargets)
    {
        $messagetargets->setMessage($this);
        $this->messagetargets[] = $messagetargets;
    }
    
    public function addMessagetargets(\Dodici\Fansworld\WebBundle\Entity\MessageTarget $messagetargets)
    {
        $this->addMessageTarget($messagetargets);
    }

    /**
     * Get messagetargets
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getMessagetargets()
    {
        return $this->messagetargets;
    }
    
    public function setMessagetargets($messagetargets)
    {
        $this->messagetargets = $messagetargets;
    }
}