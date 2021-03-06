<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Dodici\Fansworld\WebBundle\Model\VisitableInterface;
use Dodici\Fansworld\WebBundle\Model\SearchableInterface;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dodici\Fansworld\WebBundle\Entity\Idol
 *
 * A sports player or similar. Can be followed by users (become a fan, Idolship).
 * Contents can be tagged with idols (HasIdol).
 *
 * @ORM\Table(name="idol")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\IdolRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Idol implements SearchableInterface, VisitableInterface
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
     * @var text $achievements
     *
     * @ORM\Column(name="achievements", type="text", nullable=true)
     */
    private $achievements;

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
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     */
    private $image;

    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="splash", referencedColumnName="id")
     */
    private $splash;

    /**
     * @var string $origin
     *
     * @ORM\Column(name="origin", type="string", length=250, nullable=true)
     */
    private $origin;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;

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
     * @var string $external
     *
     * @ORM\Column(name="external", type="string", length=100, nullable=true)
     */
    private $external;

    /**
     * @var string $jobname
     *
     * @ORM\Column(name="jobname", type="string", length=250, nullable=true)
     */
    private $jobname;

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
     * @var integer $photoCount
     * @ORM\Column(name="photocount", type="bigint", nullable=false)
     */
    private $photoCount;

    /**
     * @var integer $videoCount
     * @ORM\Column(name="videocount", type="bigint", nullable=false)
     */
    private $videoCount;

    /**
     * @ORM\OneToMany(targetEntity="Visit", mappedBy="idol", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $visits;

    /**
     * @var integer $visitCount
     *
     * @ORM\Column(name="visitcount", type="integer", nullable=false)
     */
    private $visitCount;

    /**
     * @ORM\OneToMany(targetEntity="Idolship", mappedBy="idol", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $idolships;

    /**
     * @var Genre
     *
     * @Assert\NotNull()
     *
     * @ORM\ManyToOne(targetEntity="Genre")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="genre_id", referencedColumnName="id")
     * })
     */
    private $genre;

    /**
     * @ORM\OneToMany(targetEntity="HasGenres", mappedBy="idol", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $hasgenres;


    public function __construct()
    {
        $this->visits = new \Doctrine\Common\Collections\ArrayCollection();
        $this->visitCount = 0;
        $this->idolcareers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->hasgenres = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
        if (null === $this->photoCount) {
            $this->setPhotoCount(0);
        }

        if (null === $this->videoCount) {
            $this->setVideoCount(0);
        }

    }

    public function __toString()
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

    /**
     * Get TeamName
     * Return the actual idol career
     * @return Dodici\Fansworld\WebBundle\Entity\IdolCareer
     */
    public function getTeamName()
    {
        foreach ($this->idolcareers as $ic) {
            if ($ic->getActual()) {
                return $ic;
            }
        }
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
     * Set achievements
     *
     * @param text $achievements
     */
    public function setAchievements($achievements)
    {
        $this->achievements = $achievements;
    }

    /**
     * Get achievements
     *
     * @return text
     */
    public function getAchievements()
    {
        return $this->achievements;
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
        $idolcareers->setIdol($this);
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
     * Set photoCount
     *
     * @param bigint $photoCount
     */
    public function setPhotoCount($photoCount)
    {
        $this->photoCount = $photoCount;
    }

    /**
     * Get photoCount
     *
     * @return bigint
     */
    public function getPhotoCount()
    {
        return $this->photoCount;
    }

    /**
     * Set videoCount
     *
     * @param bigint $videoCount
     */
    public function setVideoCount($videoCount)
    {
        $this->videoCount = $videoCount;
    }

    /**
     * Get videoCount
     *
     * @return bigint
     */
    public function getVideoCount()
    {
        return $this->videoCount;
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

    /**
     * Set country
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Country $country
     */
    public function setCountry(\Dodici\Fansworld\WebBundle\Entity\Country $country)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Add visits
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Visit $visits
     */
    public function addVisit(\Dodici\Fansworld\WebBundle\Entity\Visit $visits)
    {
        $visits->setIdol($this);
        $this->setVisitCount($this->getVisitCount() + 1);
        $this->visits[] = $visits;
    }

    public function addVisits(\Dodici\Fansworld\WebBundle\Entity\Visit $visits)
    {
        $this->addVisit($visits);
    }

    /**
     * Get visits
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getVisits()
    {
        return $this->visits;
    }

    public function setVisits($visits)
    {
        $this->visits = $visits;
    }

    /**
     * Set visitCount
     *
     * @param integer $visitCount
     */
    public function setVisitCount($visitCount)
    {
        $this->visitCount = $visitCount;
    }

    /**
     * Get visitCount
     *
     * @return integer
     */
    public function getVisitCount()
    {
        return $this->visitCount;
    }

    /**
     * Set external
     *
     * @param string $external
     */
    public function setExternal($external)
    {
        $this->external = $external;
    }

    /**
     * Get external
     *
     * @return string
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * Add idolships
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Idolship $idolships
     */
    public function addIdolship(\Dodici\Fansworld\WebBundle\Entity\Idolship $idolships)
    {
        $this->idolships[] = $idolships;
    }

    /**
     * Get idolships
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getIdolships()
    {
        return $this->idolships;
    }

    /**
     * Set jobname
     *
     * @param string $jobname
     */
    public function setJobname($jobname)
    {
        $this->jobname = $jobname;
    }

    /**
     * Get jobname
     *
     * @return string
     */
    public function getJobname()
    {
        return $this->jobname;
    }

    /**
     * Set Genre
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Genre $genre
     */
    public function setGenre(\Dodici\Fansworld\WebBundle\Entity\Genre $genre)
    {
        $this->genre = $genre;
    }

    /**
     * Get Genre
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Genre
     */
    public function getGenre()
    {
        return $this->genre;
    }

     /**
     * Add hasgenre
     *
     * @param Dodici\Fansworld\WebBundle\Entity\HasGenres $hasgenres
     */
    public function addHasGenre(\Dodici\Fansworld\WebBundle\Entity\HasGenres $hasgenres)
    {
        $this->hasgenres[] = $hasgenres;
    }

    /**
     * Get hasgenres
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getHasGenre()
    {
        return $this->hasgenres;
    }

}