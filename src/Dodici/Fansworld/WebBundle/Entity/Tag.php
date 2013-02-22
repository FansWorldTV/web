<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Dodici\Fansworld\WebBundle\Entity\Tag
 *
 * @ORM\Table(name="tag")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\TagRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Tag
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
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=false, unique=true)
     */
    private $title;

    /**
     * @var integer $useCount
     *
     * @ORM\Column(name="useCount", type="integer", nullable=false)
     */
    private $useCount;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
        
	/**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=250)
     */
    private $slug;

	public function __toString() {
		return $this->getTitle();
	}
	
	/**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if (null === $this->createdAt) {
            $this->setCreatedAt(new \DateTime());
        }
        if (null === $this->useCount) {
        	$this->setUseCount(0);
        }
        $this->setTitle(mb_strtolower($this->getTitle(), 'UTF-8'));
    }
    
	public function useUp()
    {
    	$this->setUseCount($this->getUseCount() + 1);
    }
    public function useDown()
    {
    	if ($this->getUseCount() > 0) {
    		$this->setUseCount($this->getUseCount() - 1);
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
     * Set useCount
     *
     * @param integer $useCount
     */
    public function setUseCount($useCount)
    {
        $this->useCount = $useCount;
    }

    /**
     * Get useCount
     *
     * @return integer 
     */
    public function getUseCount()
    {
        return $this->useCount;
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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }
}