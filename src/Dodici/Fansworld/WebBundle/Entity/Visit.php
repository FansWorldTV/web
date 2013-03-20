<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\Visit
 *
 * A visit to a site content
 *
 * @ORM\Table(name="visit")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\VisitRepository")
 */
class Visit
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
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $author;

    /**
     * @var string $ip
     *
     * @ORM\Column(name="ip", type="string", length=100, nullable=true)
     */
    private $ip;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var Video
     *
     * @ORM\ManyToOne(targetEntity="Video")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="video_id", referencedColumnName="id")
     * })
     */
    private $video;

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
     * @var Idol
     *
     * @ORM\ManyToOne(targetEntity="Idol")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idol_id", referencedColumnName="id")
     * })
     */
    private $idol;

    /**
     * @var Photo
     *
     * @ORM\ManyToOne(targetEntity="Photo")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="photo_id", referencedColumnName="id")
     * })
     */
    private $photo;

    /**
     * @var Application\Sonata\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="target_id", referencedColumnName="id")
     * })
     */
    private $target;

    /**
     * @var string $device
     *
     * @ORM\Column(name="device", type="string", length=100, nullable=true)
     */
    private $device;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
     * Set ip
     *
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
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
     * Set video
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Video $video
     */
    public function setVideo(\Dodici\Fansworld\WebBundle\Entity\Video $video)
    {
        $this->video = $video;
    }

    /**
     * Get video
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Video
     */
    public function getVideo()
    {
        return $this->video;
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

    /**
     * Set photo
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Photo $photo
     */
    public function setPhoto(\Dodici\Fansworld\WebBundle\Entity\Photo $photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get photo
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set target
     *
     * @param Application\Sonata\UserBundle\Entity\User $target
     */
    public function setTarget(\Application\Sonata\UserBundle\Entity\User $target)
    {
        $this->target = $target;
    }

    /**
     * Get target
     *
     * @return Application\Sonata\UserBundle\Entity\User
     */
    public function getTarget()
    {
        return $this->target;
    }

     /**
     * Set device
     *
     * @param string $device
     */
    public function setDevice($device)
    {
        $this->device = $device;
    }

    /**
     * Get device
     *
     * @return string
     */
    public function getDevice()
    {
        return $this->device;
    }
}