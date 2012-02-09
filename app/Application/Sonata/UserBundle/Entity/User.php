<?php
/**
 * This file is part of the <name> project.
 *
 * (c) <yourname> <youremail>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Sonata\UserBundle\Entity;

use Sonata\UserBundle\Entity\BaseUser as BaseUser;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Application\Sonata\UserBundle\Entity\User
 */
class User extends BaseUser
{
	const SEX_MALE = 'm';
	const SEX_FEMALE = 'f';
	
	const TYPE_FAN = 1;
	const TYPE_IDOL = 2;
	const TYPE_STAFF = 3;
	
    /**
     * @var integer $id
     */
    protected $id;
    
    /**
     * @var string $address
     */
    private $address;
    
    /**
     * @var string $firstname
     */
    private $firstname;
    
    /**
     * @var string $lastname
     */
    private $lastname;
    
    /**
     * @var string $phone
     */
    private $phone;
    
    /**
     * @var string $mobile
     */
    private $mobile;
    
    /**
     * @var string $sex
     */
    private $sex;
    
    /**
     * @var integer $score
     */
    private $score;
    
    /**
     * @var string
     */
    protected $facebookId;
    
    /**
     * @var integer $type
     */
    protected $type;
    
    /**
     * @var datetime $birthday
     */
    private $birthday;
    
    /**
     * @var Dodici\Fansworld\WebBundle\Entity\Country
     */
    private $country;
    
    /**
     * @var Dodici\Fansworld\WebBundle\Entity\City
     */
    private $city;
    
    /**
     * @var Dodici\Fansworld\WebBundle\Entity\Level
     */
    private $level;
    
    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     */
    private $image;
    
    /**
     * @var array $privacy
     * array (
     * 'fieldname' => Privacy::EVERYONE|Privacy::FRIENDS_ONLY,
     * ...
     * )
     */
    private $privacy;

	/**
     * @var ArrayCollection $friendships
     */
    protected $friendships;
    
    /**
     * @var ArrayCollection $idolships
     */
    protected $idolships;
    
    public function __construct()
    {
        parent::__construct();
    	$this->friendships = new ArrayCollection();
    	$this->idolships = new ArrayCollection();
        $this->privacy = array();
    }
    
    public function __toString()
    {
    	if ($this->getFirstname() || $this->getLastname()) {
    		return join(' ', array($this->getFirstname(), $this->getLastname()));
    	} else {
    		return $this->getUsername() ?: $this->getEmail();
    	}
    }
    
	public function serialize()
    {
        return serialize(array($this->facebookId, parent::serialize()));
    }

    public function unserialize($data)
    {
        list($this->facebookId, $parentData) = unserialize($data);
        parent::unserialize($parentData);
    }
    
    public function prePersist()
    {
        parent::prePersist();
    	if (null === $this->type) {
            $this->setType(self::TYPE_FAN);
        }
    }
    
	/**
	 * @param string $facebookId
     * @return void
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;
        $this->setUsername($facebookId);
        $this->salt = '';
    }

    /**
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * @param Array
     */
    public function setFBData($fbdata)
    {
        if (isset($fbdata['id'])) {
            $this->setFacebookId($fbdata['id']);
            $this->addRole('ROLE_FACEBOOK');
        }
        if (isset($fbdata['first_name'])) {
            $this->setFirstname($fbdata['first_name']);
        }
        if (isset($fbdata['last_name'])) {
            $this->setLastname($fbdata['last_name']);
        }
        if (isset($fbdata['email'])) {
            $this->setEmail($fbdata['email']);
        }
    }
    
    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
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
     * Set phone
     *
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
    }

    /**
     * Get mobile
     *
     * @return string 
     */
    public function getMobile()
    {
        return $this->mobile;
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
     * Set privacy
     *
     * @param array $privacy
     */
    public function setPrivacy($privacy)
    {
        $this->privacy = $privacy;
    }

    /**
     * Get privacy
     *
     * @return array 
     */
    public function getPrivacy()
    {
        return $this->privacy;
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
     * Set city
     *
     * @param Dodici\Fansworld\WebBundle\Entity\City $city
     */
    public function setCity(\Dodici\Fansworld\WebBundle\Entity\City $city)
    {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return Dodici\Fansworld\WebBundle\Entity\City 
     */
    public function getCity()
    {
        return $this->city;
    }
    
	/**
     * Get level
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Level 
     */
    public function getLevel()
    {
        return $this->level;
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
     * Add friendships
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Friendship $friendships
     */
    public function addFriendship(\Dodici\Fansworld\WebBundle\Entity\Friendship $friendships)
    {
        $this->friendships[] = $friendships;
    }
	public function addFriendships(\Dodici\Fansworld\WebBundle\Entity\Friendship $friendships)
    {
        $this->addFriendship($friendships);
    }

    /**
     * Get friendships
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getFriendships()
    {
        return $this->friendships;
    }
    
	/**
     * Set friendships
     *
     * @param Doctrine\Common\Collections\Collection $friendships
     */
    public function setFriendships($friendships)
    {
        $this->friendships = $friendships;
    }
    
	/**
     * Add idolships
     *
     * @param \Dodici\Fansworld\WebBundle\Entity\Idolship $idolships
     */
    public function addIdolship(\Dodici\Fansworld\WebBundle\Entity\Idolship $idolships)
    {
        $this->idolships[] = $idolships;
    }
	public function addIdolships(\Dodici\Fansworld\WebBundle\Entity\Idolship $idolships)
    {
        $this->addIdolship($idolships);
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
     * Set idolships
     *
     * @param Doctrine\Common\Collections\Collection $idolships
     */
    public function setIdolships($idolships)
    {
        $this->idolships = $idolships;
    }

}