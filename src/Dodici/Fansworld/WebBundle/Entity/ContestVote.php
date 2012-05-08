<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\ContestVote
 * 
 * A user's vote of a contest's submission. Users should only vote once in each contest
 *
 * @ORM\Table(name="contest_vote")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\ContestVoteRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ContestVote
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
     * @var ContestParticipant
     *
     * @ORM\ManyToOne(targetEntity="ContestParticipant")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contestparticipant_id", referencedColumnName="id")
     * })
     */
    private $contestparticipant;
    
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
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if (null === $this->createdAt) {
            $this->setCreatedAt(new \DateTime());
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
     * Set contestparticipant
     *
     * @param Dodici\Fansworld\WebBundle\Entity\ContestParticipant $contestparticipant
     */
    public function setContestparticipant(\Dodici\Fansworld\WebBundle\Entity\ContestParticipant $contestparticipant)
    {
        $this->contestparticipant = $contestparticipant;
    }

    /**
     * Get contestparticipant
     *
     * @return Dodici\Fansworld\WebBundle\Entity\ContestParticipant 
     */
    public function getContestparticipant()
    {
        return $this->contestparticipant;
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