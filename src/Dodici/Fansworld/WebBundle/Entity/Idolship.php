<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Idolship
 * 
 * A user following an idol. Will receive activity updates, etc.
 *
 * @ORM\Table(name="idolship")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\IdolshipRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Idolship
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
     * @var Idol
     *
     * @ORM\ManyToOne(targetEntity="Idol")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idol_id", referencedColumnName="id")
     * })
     */
    private $idol;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
    /**
     * @var boolean $favorite
     *
     * @ORM\Column(name="favorite", type="boolean", nullable=false)
     */
    private $favorite;
    
    /**
     * @var bigint $score
     *
     * @ORM\Column(name="score", type="bigint", nullable=false)
     */
    private $score;
    
	/**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if (null === $this->favorite) {
            $this->setFavorite(false);
        }
    	if (null === $this->score) {
            $this->setScore(0);
        }
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
     * Set favorite
     *
     * @param boolean $favorite
     */
    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;
    }

    /**
     * Get favorite
     *
     * @return boolean 
     */
    public function getFavorite()
    {
        return $this->favorite;
    }

    /**
     * Set score
     *
     * @param bigint $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * Get score
     *
     * @return bigint 
     */
    public function getScore()
    {
        return $this->score;
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
     * Set idol
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Idol $idol
     */
    public function setIdol(\Dodici\Fansworld\WebBundle\Entity\Idol $idol)
    {
        $this->idol = $idol;
    }

    /**
     * Get idol
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Idol 
     */
    public function getIdol()
    {
        return $this->idol;
    }
}