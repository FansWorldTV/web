<?php

namespace DataFactory\FeedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * DataFactory\FeedBundle\Entity\XmlData
 * 
 * @ORM\Table(name="datafactory_xmldata")
 * @ORM\Entity(repositoryClass="DataFactory\FeedBundle\Model\XmlDataRepository")
 * @ORM\HasLifecycleCallbacks
 */
class XmlData
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
     * @var string $channel
     *
     * @ORM\Column(name="channel", type="string", length=100, nullable=false, unique=true)
     */
    private $channel;
    
    /**
     * @var text $data
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;
    
    /**
     * @var datetime $updated
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;
    
    /**
     * @var datetime $changed
     *
     * @ORM\Column(name="changed", type="datetime", nullable=false)
     */
    private $changed;
    
    /**
     * @var datetime $processed
     *
     * @ORM\Column(name="processed", type="datetime", nullable=false)
     */
    private $processed;
    
    public function __construct()
    {
        $date = new \DateTime();
        $this->updated = $date;
        $this->changed = $date;
        $this->processed = $date;
    }
    
	/**
     * @ORM\PostUpdate()
     */
    public function postUpdate()
    {
        $this->updated = new \DateTime();
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
     * Set channel
     *
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * Get channel
     *
     * @return string 
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set data
     *
     * @param text $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return text 
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set updated
     *
     * @param datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated
     *
     * @return datetime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set changed
     *
     * @param datetime $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * Get changed
     *
     * @return datetime 
     */
    public function getChanged()
    {
        return $this->changed;
    }
    
	/**
     * Set processed
     *
     * @param datetime $processed
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
    }

    /**
     * Get processed
     *
     * @return datetime 
     */
    public function getProcessed()
    {
        return $this->processed;
    }
}