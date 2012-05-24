<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Dodici\Fansworld\WebBundle\Model\SearchableInterface;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Dodici\Fansworld\WebBundle\Entity\VideoCategory
 *
 * @ORM\Table(name="idol")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\IdolRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Idol implements SearchableInterface
{
	const SEX_MALE = 'm';
	const SEX_FEMALE = 'f';
	
    /**
     * @var bigint $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $firstname
     *
     * @ORM\Column(name="firstname", type="string", length=100, nullable=false)
     */
    private $firstname;
    
    /**
     * @var string $lastname
     *
     * @ORM\Column(name="lastname", type="string", length=100, nullable=false)
     */
    private $lastname;
    
    /**
     * @var text $nicknames
     *
     * @ORM\Column(name="nicknames", type="text", nullable=true)
     */
    private $nicknames;
    
    /**
     * @var text $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;
    
    /**
     * @var datetime $birthday
     *
     * @ORM\Column(name="birthday", type="datetime", nullable=true)
     */
    private $birthday;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
        
    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
    
    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="image", referencedColumnName="id")
     */
    private $image;
    
    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="splash", referencedColumnName="id")
     */
    private $splash;
    
    /**
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="Team")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="team_id", referencedColumnName="id")
     * })
     */
    private $team;
    
    /**
     * @var string $origin
     *
     * @ORM\Column(name="origin", type="string", length=250, nullable=true)
     */
    private $origin;
    
    /**
     * @var string $sex
     * 
     * @ORM\Column(name="sex", type="string", length=10, nullable=true)
     */
    private $sex;
    
    /**
     * @var string $twitter
     *
     * @ORM\Column(name="twitter", type="string", length=100, nullable=true)
     */
    private $twitter;
    
    /**
     * @var integer $score
     * 
     * @ORM\Column(name="score", type="integer", nullable=false)
     */
    private $score;
    
    /**
     * @ORM\OneToMany(targetEntity="IdolCareer", mappedBy="idol", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $idolcareers;
    
    /**
     * @Gedmo\Slug(fields={"firstname", "lastname"}, unique=true)
     * @ORM\Column(length=250)
     */
    private $slug;
    
    /**
     * @var integer $fanCount
     *
     * @ORM\Column(name="fancount", type="integer", nullable=false)
     */
    private $fanCount;
    
	/**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if (null === $this->createdAt) {
            $this->setCreatedAt(new \DateTime());
        }
    	if (null === $this->active) {
            $this->setActive(true);
        }
        if (null === $this->fanCount) {
        	$this->setFanCount(0);
        }
    	if (null === $this->score) {
        	$this->setScore(0);
        }
    }
    
    public function __toString()
    {
    	return $this->getFirstname() . ' ' . $this->getLastname();
    }
    
    public function getTitle()
    {
    	return (string)$this;
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
     * Set firstname
     *
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set nicknames
     *
     * @param text $nicknames
     */
    public function setNicknames($nicknames)
    {
        $this->nicknames = $nicknames;
    }

    /**
     * Get nicknames
     *
     * @return text 
     */
    public function getNicknames()
    {
        return $this->nicknames;
    }

    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     *
     * @return text 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set birthday
     *
     * @param datetime $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * Get birthday
     *
     * @return datetime 
     */
    public function getBirthday()
    {
        return $this->birthday;
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
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set origin
     *
     * @param string $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    /**
     * Get origin
     *
     * @return string 
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set sex
     *
     * @param string $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    /**
     * Get sex
     *
     * @return string 
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set score
     *
     * @param integer $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * Get score
     *
     * @return integer 
     */
    public function getScore()
    {
        return $this->score;
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
     * Set image
     *
     * @param Application\Sonata\MediaBundle\Entity\Media $image
     */
    public function setImage(\Application\Sonata\MediaBundle\Entity\Media $image)
    {
        $this->image = $image;
    }

    /**
     * Get image
     *
     * @return Application\Sonata\MediaBundle\Entity\Media 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set team
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Team $team
     */
    public function setTeam(\Dodici\Fansworld\WebBundle\Entity\Team $team)
    {
        $this->team = $team;
    }

    /**
     * Get team
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Team 
     */
    public function getTeam()
    {
        return $this->team;
    }
    
	/**
     * Add idolcareers
     *
     * @param \Dodici\Fansworld\WebBundle\Entity\IdolCareer $idolcareers
     */
    public function addIdolcareers(\Dodici\Fansworld\WebBundle\Entity\IdolCareer $idolcareers)
    {
        $this->addIdolcareer($idolcareers);
    }
    
	/**
     * Add idolcareers
     *
     * @param Dodici\Fansworld\WebBundle\Entity\IdolCareer $idolcareers
     */
    public function addIdolCareer(\Dodici\Fansworld\WebBundle\Entity\IdolCareer $idolcareers)
    {
        $this->idolcareers[] = $idolcareers;
    }

    /**
     * Get idolcareers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getIdolcareers()
    {
        return $this->idolcareers;
    }
    
	/**
     * Set idolcareers
     *
     * @param Doctrine\Common\Collections\Collection $idolcareers
     */
    public function setIdolcareers($idolcareers)
    {
        $this->idolcareers = $idolcareers;
    }
    public function __construct()
    {
        $this->idolcareers = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set twitter
     *
     * @param string $twitter
     */
    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
    }

    /**
     * Get twitter
     *
     * @return string 
     */
    public function getTwitter()
    {
        return $this->twitter;
    }

    /**
     * Set fanCount
     *
     * @param integer $fanCount
     */
    public function setFanCount($fanCount)
    {
        $this->fanCount = $fanCount;
    }

    /**
     * Get fanCount
     *
     * @return integer 
     */
    public function getFanCount()
    {
        return $this->fanCount;
    }

    /**
     * Set splash
     *
     * @param Application\Sonata\MediaBundle\Entity\Media $splash
     */
    public function setSplash(\Application\Sonata\MediaBundle\Entity\Media $splash)
    {
        $this->splash = $splash;
    }

    /**
     * Get splash
     *
     * @return Application\Sonata\MediaBundle\Entity\Media 
     */
    public function getSplash()
    {
        return $this->splash;
    }
}