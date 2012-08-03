<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\HomeVideo
 * 
 * A video displayed in a global home, or a channel home. Manually set in admin module.
 *
 * @ORM\Table(name="homevideo")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\HomeVideoRepository")
 */
class HomeVideo
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
     * @var Video
     *
     * @ORM\ManyToOne(targetEntity="Video", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="video_id", referencedColumnName="id")
     * })
     */
    private $video;
    
    /**
     * @var VideoCategory
     *
     * @ORM\ManyToOne(targetEntity="VideoCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="videocategory_id", referencedColumnName="id")
     * })
     */
    private $videocategory;
    
    /**
     * @var integer $position
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private $position;

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
     * Set position
     *
     * @param integer $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
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
     * Set videocategory
     *
     * @param Dodici\Fansworld\WebBundle\Entity\VideoCategory $videocategory
     */
    public function setVideocategory(\Dodici\Fansworld\WebBundle\Entity\VideoCategory $videocategory)
    {
        $this->videocategory = $videocategory;
    }

    /**
     * Get videocategory
     *
     * @return Dodici\Fansworld\WebBundle\Entity\VideoCategory 
     */
    public function getVideocategory()
    {
        return $this->videocategory;
    }
}