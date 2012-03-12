<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\HasInterest
 *
 * @ORM\Table(name="hasinterest")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\HasInterestRepository")
 */
class HasInterest
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
     * @var Interest
     *
     * @ORM\ManyToOne(targetEntity="Interest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="interest_id", referencedColumnName="id")
     * })
     */
    private $interest;
    
    /**
     * @var boolean $career
     * 
     * @ORM\Column(name="career", type="boolean", nullable=false)
     */
    private $career;
    
    /**
     * @var string $position
     *
     * @ORM\Column(name="position", type="string", length=250, nullable=true)
     */
    private $position;
    
    /**
     * @var datetime $dateFrom
     *
     * @ORM\Column(name="datefrom", type="datetime", nullable=true)
     */
    private $dateFrom;
    
    /**
     * @var datetime $dateTo
     *
     * @ORM\Column(name="dateto", type="datetime", nullable=true)
     */
    private $dateTo;
    
	public function __construct()
    {
        $this->career = false;
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
     * Set career
     *
     * @param boolean $career
     */
    public function setCareer($career)
    {
        $this->career = $career;
    }

    /**
     * Get career
     *
     * @return boolean 
     */
    public function getCareer()
    {
        return $this->career;
    }

    /**
     * Set position
     *
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Get position
     *
     * @return string 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set dateFrom
     *
     * @param datetime $dateFrom
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
    }

    /**
     * Get dateFrom
     *
     * @return datetime 
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * Set dateTo
     *
     * @param datetime $dateTo
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
    }

    /**
     * Get dateTo
     *
     * @return datetime 
     */
    public function getDateTo()
    {
        return $this->dateTo;
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
     * Set interest
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Interest $interest
     */
    public function setInterest(\Dodici\Fansworld\WebBundle\Entity\Interest $interest)
    {
        $this->interest = $interest;
    }

    /**
     * Get interest
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Interest 
     */
    public function getInterest()
    {
        return $this->interest;
    }
}