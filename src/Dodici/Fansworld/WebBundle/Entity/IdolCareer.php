<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\IdolCareer
 * 
 * Idol career history in teams (or teamname if team does not exist in FW)
 *
 * @ORM\Table(name="idol_career")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class IdolCareer implements Translatable
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
     * @var Idol
     *
     * @ORM\ManyToOne(targetEntity="Idol")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idol_id", referencedColumnName="id")
     * })
     */
    private $idol;
    
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
     * @var string $teamname
     * override
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="teamname", type="string", length=250, nullable=true)
     */
    private $teamname;
    
    /**
     * @var string $position
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="position", type="string", length=250, nullable=true)
     */
    private $position;

    /**
     * @var text $content
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
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
    
    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;
            
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
    
    public function __toString()
    {
    	return ($this->getTeam() ? (string)$this->getTeam() : $this->getTeamName()) . ' - ' . $this->getPosition();
    }
    
	/**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
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
     * Set teamname
     *
     * @param string $teamname
     */
    public function setTeamname($teamname)
    {
        $this->teamname = $teamname;
    }

    /**
     * Get teamname
     *
     * @return string 
     */
    public function getTeamname()
    {
        return $this->teamname;
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
}