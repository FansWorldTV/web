<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\InterestCategory
 *
 * @ORM\Table(name="interestcategory")
 * @ORM\Entity
 */
class InterestCategory implements Translatable
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
     * @var string $title
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=false)
     */
    private $title;
    
	/**
	 * @Gedmo\Locale
	 * Used locale to override Translation listener`s locale
	 * this is not a mapped field of entity metadata, just a simple property
	 */
	private $locale;
	
	public function setTranslatableLocale($locale)
	{
	    $this->locale = $locale;
	}

	/**
     * @ORM\OneToMany(targetEntity="Interest", mappedBy="interestcategory", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $interests;

    public function __construct()
    {
        $this->interests = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
	public function __toString()
    {
    	return $this->getTitle();
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
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Add interests
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Interest $interests
     */
    public function addInterest(\Dodici\Fansworld\WebBundle\Entity\Interest $interests)
    {
        $this->interests[] = $interests;
    }

    /**
     * Get interests
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getInterests()
    {
        return $this->interests;
    }
}