<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\OpinionVote
 *
 * @ORM\Table(name="opinionvote")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\OpinionVoteRepository")
 */
class OpinionVote
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
     * @var boolean $value
     *
     * @ORM\Column(name="value", type="boolean", nullable=false)
     */
    private $value;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
    /**
     * @var Opinion
     *
     * @ORM\ManyToOne(targetEntity="Opinion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="opinion_id", referencedColumnName="id")
     * })
     */
    private $opinion;
    
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
     * Set value
     *
     * @param boolean $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get value
     *
     * @return boolean 
     */
    public function getValue()
    {
        return $this->value;
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
     * Set opinion
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Opinion $opinion
     */
    public function setOpinion(\Dodici\Fansworld\WebBundle\Entity\Opinion $opinion)
    {
        $this->opinion = $opinion;
    }

    /**
     * Get opinion
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Opinion 
     */
    public function getOpinion()
    {
        return $this->opinion;
    }
}