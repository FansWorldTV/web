<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Meetingship
 * 
 * A user's participation in a meeting
 *
 * @ORM\Table(name="meetingship")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Meetingship
{
    const TYPE_ATTEND = 1;
    const TYPE_MAYBE = 2;
    const TYPE_STAY = 3;
    
    public static function getTypes()
    {
    	return array(
    		self::TYPE_ATTEND => 'Asiste',
    		self::TYPE_MAYBE => 'Quizás',
    		self::TYPE_STAY => 'No asiste',
    	);
    }
    
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
     * @var Meeting
     *
     * @ORM\ManyToOne(targetEntity="Meeting")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="meeting_id", referencedColumnName="id")
     * })
     */
    private $meeting;
    
    /**
     * @var integer $type
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
        
	/**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if (null === $this->createdAt) {
            $this->setCreatedAt(new \DateTime());
        }
        if (null === $this->type) {
        	$this->setType(self::TYPE_ATTEND);
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
}