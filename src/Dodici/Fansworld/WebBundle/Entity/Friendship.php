<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Dodici\Fansworld\WebBundle\Entity\Friendship
 * 
 * A user following another user. Non-reciprocal. Will need to be activated if the target user is in restricted mode.
 *
 * @ORM\Table(name="friendship")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\FriendshipRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Friendship
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
     *   @ORM\JoinColumn(name="author_id", referencedColumnName="id")
     * })
     */
    private $author;
    
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
     * @var boolean $invitation
     *
     * @ORM\Column(name="invitation", type="boolean", nullable=false)
     */
    private $invitation;
    
    /**
     * @ORM\ManyToMany(targetEntity="FriendGroup")
     * @ORM\JoinTable(name="friendship_friendgroup",
     *      joinColumns={@ORM\JoinColumn(name="friendship_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="friendgroup_id", referencedColumnName="id")}
     *      )
     */
    protected $friendgroups;
    
	public function __construct()
    {
        $this->friendgroups = new ArrayCollection();
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
        if (null === $this->invitation) {
        	$this->setInvitation(false);
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
     * Add friendgroups
     *
     * @param Dodici\Fansworld\WebBundle\Entity\FriendGroup $friendgroups
     */
    public function addFriendGroup(\Dodici\Fansworld\WebBundle\Entity\FriendGroup $friendgroups)
    {
        $this->friendgroups[] = $friendgroups;
    }
    
    public function addFriendgroups($friendgroups){
        $this->addFriendGroup($friendgroups);
    }
    
    public function setFriendgroups($friendgroups){
        foreach($friendgroups as $friendgroup){
            $this->addFriendGroup($friendgroup);
        }
    }

    /**
     * Get friendgroups
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getFriendgroups()
    {
        return $this->friendgroups;
    }

    /**
     * Set invitation
     *
     * @param boolean $invitation
     */
    public function setInvitation($invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get invitation
     *
     * @return boolean 
     */
    public function getInvitation()
    {
        return $this->invitation;
    }
}