<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\VideoCategory
 * 
 * Video categories defined by FW.
 *
 * @ORM\Table(name="videocategory")
 * @ORM\Entity
 */
class VideoCategory implements Translatable
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
     * @Gedmo\Slug(fields={"title"}, unique=false)
     * @Gedmo\Translatable
     * @ORM\Column(length=128)
     */
    private $slug;
    
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
     * @ORM\OneToMany(targetEntity="Video", mappedBy="videocategory", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $videos;
    
    /**
     * @ORM\ManyToMany(targetEntity="Video")
     * @ORM\JoinTable(name="video_channel_home",
     *      joinColumns={@ORM\JoinColumn(name="videocategory_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="video_id", referencedColumnName="id")}
     *      )
     */
    protected $homevideos;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
        $this->homevideos = new ArrayCollection();
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

    /**
     * Add videos
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Video $videos
     */
    public function addVideo(\Dodici\Fansworld\WebBundle\Entity\Video $videos)
    {
        $this->videos[] = $videos;
    }

    /**
     * Get videos
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getVideos()
    {
        return $this->videos;
    }
    
	/**
     * Add homevideos
     *
     * @param Dodici\Fansworld\WebBundle\Entity\TeamCategory $homevideos
     */
    public function addHomevideo(\Dodici\Fansworld\WebBundle\Entity\Video $homevideos)
    {
        $this->homevideos[] = $homevideos;
    }
    
    public function addHomevideos(\Dodici\Fansworld\WebBundle\Entity\Video $homevideos)
    {
        $this->addHomevideo($homevideos);
    }

    /**
     * Get homevideos
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getHomevideos()
    {
        return $this->homevideos;
    }
    
    public function setHomevideos($homevideos)
    {
        $this->homevideos = $homevideos;
    }
}