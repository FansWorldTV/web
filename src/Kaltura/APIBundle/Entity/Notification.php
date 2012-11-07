<?php

namespace Kaltura\APIBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Kaltura\APIBundle\Entity\Notification
 * 
 * @ORM\Table(name="kaltura_notification")
 * @ORM\Entity
 */
class Notification
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
     * @var string $external
     *
     * @ORM\Column(name="external", type="string", length=250, nullable=false, unique=true)
     */
    private $external;
    
    /**
     * @var array $data
     *
     * @ORM\Column(name="data", type="array", nullable=true)
     */
    private $data;
    
    /**
     * @var datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;
        
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
     * Set data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return array 
     */
    public function getData()
    {
        return $this->data;
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
}