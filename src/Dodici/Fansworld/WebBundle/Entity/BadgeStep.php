<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\BadgeStep
 * 
 * Achievement badge step
 *
 * @ORM\Table(name="badgestep")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\BadgeStepRepository")
 */
class BadgeStep
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
     * @var Application\Sonata\MediaBundle\Entity\Media
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media")
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     */
    private $image;
    
    /**
     * @var integer $minimum
     *
     * @ORM\Column(name="minimum", type="integer", nullable=false)
     */
    private $minimum;
    
    /**
     * @var Badge
     *
     * @ORM\ManyToOne(targetEntity="Badge")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="badge_id", referencedColumnName="id")
     * })
     */
    private $badge;

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
     * Set minimum
     *
     * @param integer $minimum
     */
    public function setMinimum($minimum)
    {
        $this->minimum = $minimum;
    }

    /**
     * Get minimum
     *
     * @return integer 
     */
    public function getMinimum()
    {
        return $this->minimum;
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
     * Set badge
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Badge $badge
     */
    public function setBadge(\Dodici\Fansworld\WebBundle\Entity\Badge $badge)
    {
        $this->badge = $badge;
    }

    /**
     * Get badge
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Badge 
     */
    public function getBadge()
    {
        return $this->badge;
    }
}