<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\ContestParticipant
 *
 * @ORM\Table(name="contest_participant")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\ContestParticipantRepository")
 */
class ContestParticipant
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
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
    /**
     * @var boolean $winner
     *
     * @ORM\Column(name="winner", type="boolean", nullable=false)
     */
    private $winner;
    
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
     * @var Application\Sonata\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $author;

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
     * Set winner
     *
     * @param boolean $winner
     */
    public function setWinner($winner)
    {
        $this->winner = $winner;
    }

    /**
     * Get winner
     *
     * @return boolean 
     */
    public function getWinner()
    {
        return $this->winner;
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
}