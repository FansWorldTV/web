<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\HasBadge
 * 
 * A user has a badge.
 *
 * @ORM\Table(name="hasbadge")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\HasBadgeRepository")
 */
class HasBadge
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
     * @var BadgeStep
     *
     * @ORM\ManyToOne(targetEntity="BadgeStep")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="badgestep_id", referencedColumnName="id")
     * })
     */
    private $badgestep;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
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
     * Set badgestep
     *
     * @param Dodici\Fansworld\WebBundle\Entity\BadgeStep $badgestep
     */
    public function setBadgestep(\Dodici\Fansworld\WebBundle\Entity\BadgeStep $badgestep)
    {
        $this->badgestep = $badgestep;
    }

    /**
     * Get badgestep
     *
     * @return Dodici\Fansworld\WebBundle\Entity\BadgeStep 
     */
    public function getBadgestep()
    {
        return $this->badgestep;
    }
}