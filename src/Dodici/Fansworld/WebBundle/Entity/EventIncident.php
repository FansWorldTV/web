<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dodici\Fansworld\WebBundle\Entity\EventIncident
 * 
 * An incident in a DF-enabled event (goal, card, etc)
 *
 * @ORM\Table(name="event_incident")
 * @ORM\Entity
 */
class EventIncident
{
    const TYPE_GOAL = 1;
    
    public static function translateType($dftype) {
        $types = array(
            'gol' => self::TYPE_GOAL
        );
        
        return isset($types[$dftype]) ? $types[$dftype] : false;
    }
    
    /**
     * @var bigint $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     * })
     */
    private $event;
    
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
     * @var integer $type
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
    
    /**
     * @var string $external
     *
     * @ORM\Column(name="external", type="string", length=100, nullable=false)
     */
    private $external;
    
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
     * @var string $playername
     *
     * @ORM\Column(name="playername", type="string", length=200, nullable=true)
     */
    private $playername;
    
    /**
     * @var string $minute
     *
     * @ORM\Column(name="minute", type="string", length=50, nullable=true)
     */
    private $minute;
    
    /**
     * @var string $half
     *
     * @ORM\Column(name="half", type="string", length=50, nullable=true)
     */
    private $half;
    
    public function __construct() {
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
     * Set type
     *
     * @param integer $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
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
     * Set playername
     *
     * @param string $playername
     */
    public function setPlayername($playername)
    {
        $this->playername = $playername;
    }

    /**
     * Get playername
     *
     * @return string 
     */
    public function getPlayername()
    {
        return $this->playername;
    }

    /**
     * Set event
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Event $event
     */
    public function setEvent(\Dodici\Fansworld\WebBundle\Entity\Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get event
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Event 
     */
    public function getEvent()
    {
        return $this->event;
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
     * Set minute
     *
     * @param string $minute
     */
    public function setMinute($minute)
    {
        $this->minute = $minute;
    }

    /**
     * Get minute
     *
     * @return string 
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Set half
     *
     * @param string $half
     */
    public function setHalf($half)
    {
        $this->half = $half;
    }

    /**
     * Get half
     *
     * @return string 
     */
    public function getHalf()
    {
        return $this->half;
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
}