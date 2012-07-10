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

use Dodici\Fansworld\WebBundle\Entity\Privacy;

use Dodici\Fansworld\WebBundle\Model\VisitableInterface;

use Dodici\Fansworld\WebBundle\Entity\HasBadge;

use Dodici\Fansworld\WebBundle\Entity\BadgeStep;

use Dodici\Fansworld\WebBundle\Model\SearchableInterface;

use Dodici\Fansworld\WebBundle\Entity\Notification;
use Symfony\Component\Validator\Constraints as Assert;
use Sonata\UserBundle\Entity\BaseUser as BaseUser;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Application\Sonata\UserBundle\Entity\User
 */
class User extends BaseUser implements SearchableInterface, VisitableInterface
{
	const SEX_MALE = 'm';
	const SEX_FEMALE = 'f';
	
	const TYPE_FAN = 1;
	//const TYPE_IDOL = 2;
	const TYPE_STAFF = 3;
	
	/**
     * @var string
     * @Assert\Regex(pattern="/^[a-zA-Z0-9.\-]+$/", message="Only use a-z 0-9 . -", groups={"Registration", "Profile"})
     * @Assert\Regex(pattern="/[0-9.\-]+$/", message="Using only numbers/symbols not allowed", match=false, groups={"Registration", "Profile"})
     * @Assert\MinLength(limit="3", message="Min 3 letters.", groups={"Registration", "Profile"})
     * @Assert\MaxLength(limit="30", message="Max 30 letters.", groups={"Registration", "Profile"})
     */
    protected $username;
	
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
     * @var string $skype
     */
    private $skype;
    
    /**
     * @var string $msn
     */
    private $msn;
    
    /**
     * @var string $twitter
     */
    private $twitter;
    
    /**
     * @var string $twittertoken
     */
    private $twittertoken;
    
    /**
     * @var string $twittersecret
     */
    private $twittersecret;
    
    /**
     * @var string $twitterid
     */
    private $twitterid;
    
    /**
     * @var boolean $linktwitter
     */
    private $linktwitter;
    
    /**
     * @var string $yahoo
     */
    private $yahoo;
    
    /**
     * @var string $gmail
     */
    private $gmail;
    
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
     * @var Dodici\Fansworld\WebBundle\Entity\Team
     */
    private $team;
    
    /**
     * @var text $content
     */
    private $content;
    
    /**
     * @var string
     */
    private $origin;
        
    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     */
    private $image;
    
    /**
     * @var Application\Sonata\MediaBundle\Entity\Media
     */
    private $splash;
    
    /**
     * @var array $privacy
     * array (
     * 'fieldname' => Privacy::*,
     * ...
     * )
     */
    private $privacy;
    
    /**
     * @var boolean $restricted
     */
    private $restricted;
    
    /**
     * @var boolean $linkfacebook
     */
    private $linkfacebook;
    
    /**
     * @var array $notifyprefs
     * array (
     * Notification::TYPE_*,
     * ...
     * )
     */
    private $notifyprefs;
    
    /**
     * @var array $notifymail
     * array (
     * Notification::TYPE_*,
     * ...
     * )
     */
    private $notifymail;

	/**
     * @var ArrayCollection $friendships
     */
    protected $friendships;
    
    /**
     * @var ArrayCollection $friendgroups
     */
    protected $friendgroups;
    
    /**
     * @var ArrayCollection $idolships
     */
    protected $idolships;
    
    /**
     * @var ArrayCollection $hasinterests
     */
    protected $hasinterests;
    
    /**
     * @var ArrayCollection $hasbadges
     */
    protected $hasbadges;
        
    /**
     * @var integer $friendCount
     */
    private $friendCount;
    
    /**
     * @var integer $idolCount
     */
    private $idolCount;
    
    /**
     * @var ArrayCollection $visits
     */
    protected $visits;
    
    /**
     * @var integer $visitCount
     */
    private $visitCount;
        
    /**
     * @var integer $photoVisitCount
     */
    private $photoVisitCount;
    
    /**
     * @var integer $videoVisitCount
     */
    private $videoVisitCount;
    
    public function __construct()
    {
        parent::__construct();
    	$this->friendships = new ArrayCollection();
    	$this->friendgroups = new ArrayCollection();
    	$this->idolships = new ArrayCollection();
    	$this->hasinterests = new ArrayCollection();
    	$this->hasbadges = new ArrayCollection();
    	$this->privacy = Privacy::getDefaultFieldPrivacy();
        $this->notifyprefs = array_keys(Notification::getTypeList());
        $this->notifymail = array_keys(Notification::getTypeList());
        $this->idolCount = 0;
        $this->friendCount = 0;
        $this->visits = new ArrayCollection();
        $this->visitCount = 0;
        $this->photoVisitCount = 0;
        $this->videoVisitCount = 0;
    }
    
    public function __toString()
    {
    	if ($this->getFirstname() || $this->getLastname()) {
    		return join(' ', array($this->getFirstname(), $this->getLastname()));
    	} else {
    		return $this->getUsername() ?: $this->getEmail();
    	}
    }
    
    public function getTitle()
    {
    	return (string)$this;
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
        if (count($this->getFriendgroups()) <= 0) {
        	$defaultgroups = array('Amigos', 'Familia');
        	foreach ($defaultgroups as $dg) {
        		$group = new \Dodici\Fansworld\WebBundle\Entity\FriendGroup();
        		$group->setTitle($dg);
        		$group->setAuthor($this);
        		$this->addFriendgroup($group);
        	}
        }
        if (null === $this->score) {
        	$this->setScore(0);
        }
        if (null === $this->restricted) {
        	$this->setRestricted(false);
        }
    	if (null === $this->linkfacebook) {
        	$this->setLinkfacebook(false);
        }
    	if (null === $this->linktwitter) {
        	$this->setLinktwitter(false);
        }
    }
    
	/**
	 * @param string $facebookId
     * @return void
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;
        //$this->setUsername($facebookId);
        if ($this->getPassword() == '') {
        	$this->salt = '';
        }
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
        if (isset($fbdata['gender'])) {
        	if ($fbdata['gender'] == 'male') $this->setSex(self::SEX_MALE);
        	if ($fbdata['gender'] == 'female') $this->setSex(self::SEX_FEMALE);
        }
    	if (isset($fbdata['birthday'])) {
            $this->setBirthday(new \DateTime($fbdata['birthday']));
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
     * Set skype
     *
     * @param string $skype
     */
    public function setSkype($skype)
    {
        $this->skype = $skype;
    }

    /**
     * Get skype
     *
     * @return string 
     */
    public function getSkype()
    {
        return $this->skype;
    }
    
	/**
     * Set msn
     *
     * @param string $msn
     */
    public function setMsn($msn)
    {
        $this->msn = $msn;
    }

    /**
     * Get msn
     *
     * @return string 
     */
    public function getMsn()
    {
        return $this->msn;
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
     * Set twittertoken
     *
     * @param string $twittertoken
     */
    public function setTwittertoken($twittertoken)
    {
        $this->twittertoken = $twittertoken;
    }

    /**
     * Get twittertoken
     *
     * @return string 
     */
    public function getTwittertoken()
    {
        return $this->twittertoken;
    }
    
	/**
     * Set twittersecret
     *
     * @param string $twittersecret
     */
    public function setTwittersecret($twittersecret)
    {
        $this->twittersecret = $twittersecret;
    }

    /**
     * Get twittersecret
     *
     * @return string 
     */
    public function getTwittersecret()
    {
        return $this->twittersecret;
    }
    
	/**
     * Set twitterid
     *
     * @param string $twitterid
     */
    public function setTwitterid($twitterid)
    {
        $this->twitterid = $twitterid;
    }

    /**
     * Get twitterid
     *
     * @return string 
     */
    public function getTwitterid()
    {
        return $this->twitterid;
    }
    
	/**
     * Set linktwitter
     *
     * @param boolean $linktwitter
     */
    public function setLinktwitter($linktwitter)
    {
        $this->linktwitter = $linktwitter;
    }

    /**
     * Get linktwitter
     *
     * @return boolean 
     */
    public function getLinktwitter()
    {
        return $this->linktwitter;
    }
    
	/**
     * Set yahoo
     *
     * @param string $yahoo
     */
    public function setYahoo($yahoo)
    {
        $this->yahoo = $yahoo;
    }

    /**
     * Get yahoo
     *
     * @return string 
     */
    public function getYahoo()
    {
        return $this->yahoo;
    }
    
	/**
     * Set gmail
     *
     * @param string $gmail
     */
    public function setGmail($gmail)
    {
        $this->gmail = $gmail;
    }

    /**
     * Get gmail
     *
     * @return string 
     */
    public function getGmail()
    {
        return $this->gmail;
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
     * Set restricted
     *
     * @param boolean $restricted
     */
    public function setRestricted($restricted)
    {
        $this->restricted = $restricted;
    }

    /**
     * Get restricted
     *
     * @return boolean 
     */
    public function getRestricted()
    {
        return $this->restricted;
    }
    
	/**
     * Set linkfacebook
     *
     * @param boolean $linkfacebook
     */
    public function setLinkfacebook($linkfacebook)
    {
        $this->linkfacebook = $linkfacebook;
    }

    /**
     * Get linkfacebook
     *
     * @return boolean 
     */
    public function getLinkfacebook()
    {
        return $this->linkfacebook;
    }
    
	/**
     * Set notifyprefs
     *
     * @param array $notifyprefs
     */
    public function setNotifyprefs($notifyprefs)
    {
        $this->notifyprefs = $notifyprefs;
    }

    /**
     * Get notifyprefs
     *
     * @return array 
     */
    public function getNotifyprefs()
    {
        return $this->notifyprefs;
    }
    
	/**
     * Set notifymail
     *
     * @param array $notifymail
     */
    public function setNotifymail($notifymail)
    {
        $this->notifymail = $notifymail;
    }

    /**
     * Get notifymail
     *
     * @return array 
     */
    public function getNotifymail()
    {
        return $this->notifymail;
    }

    /**
     * Set country
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Country $country
     */
    public function setCountry($country)
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
    public function setCity($city)
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
     * Set level
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Level $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
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
     * Set team
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Team $team
     */
    public function setTeam($team)
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
     * Set splash
     *
     * @param Application\Sonata\MediaBundle\Entity\Media $splash
     */
    public function setImage(\Application\Sonata\MediaBundle\Entity\Media $splash)
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
    
	/**
     * Add friendgroups
     *
     * @param Dodici\Fansworld\WebBundle\Entity\FriendGroup $friendgroups
     */
    public function addFriendgroup(\Dodici\Fansworld\WebBundle\Entity\FriendGroup $friendgroups)
    {
        $this->friendgroups[] = $friendgroups;
    }
	public function addFriendgroups(\Dodici\Fansworld\WebBundle\Entity\FriendGroup $friendgroups)
    {
        $this->addFriendgroup($friendgroups);
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
     * Set friendgroups
     *
     * @param Doctrine\Common\Collections\Collection $friendgroups
     */
    public function setFriendgroups($friendgroups)
    {
        $this->friendgroups = $friendgroups;
    }
    
	/**
     * Add hasinterests
     *
     * @param \Dodici\Fansworld\WebBundle\Entity\HasInterest $hasinterests
     */
    public function addHasinterest(\Dodici\Fansworld\WebBundle\Entity\HasInterest $hasinterests)
    {
        $this->hasinterests[] = $hasinterests;
    }
	public function addHasinterests(\Dodici\Fansworld\WebBundle\Entity\HasInterest $hasinterests)
    {
        $this->addHasinterest($hasinterests);
    }

    /**
     * Get hasinterests
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getHasinterests()
    {
        return $this->hasinterests;
    }
    
	/**
     * Set hasinterests
     *
     * @param Doctrine\Common\Collections\Collection $hasinterests
     */
    public function setHasinterests($hasinterests)
    {
        $this->hasinterests = $hasinterests;
    }
    
	/**
     * Add hasbadges
     *
     * @param \Dodici\Fansworld\WebBundle\Entity\HasBadge $hasbadges
     */
    public function addHasbadge(\Dodici\Fansworld\WebBundle\Entity\HasBadge $hasbadges)
    {
        $this->hasbadges[] = $hasbadges;
    }
	public function addHasbadges(\Dodici\Fansworld\WebBundle\Entity\HasBadge $hasbadges)
    {
        $this->addHasbadge($hasbadges);
    }

    /**
     * Get hasbadges
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getHasbadges()
    {
        return $this->hasbadges;
    }
    
	/**
     * Set hasbadges
     *
     * @param Doctrine\Common\Collections\Collection $hasbadges
     */
    public function setHasbadges($hasbadges)
    {
        $this->hasbadges = $hasbadges;
    }
    
    public function addBadgeStep(BadgeStep $bs)
    {
        $hasbadge = new HasBadge();
        $hasbadge->setAuthor($this);
        $hasbadge->setBadgestep($bs);
        $this->addHasbadge($hasbadge);
    }
    
	/**
     * Set friendCount
     *
     * @param integer $friendCount
     */
    public function setFriendCount($friendCount)
    {
        if ($friendCount < 0) $friendCount = 0;
    	$this->friendCount = $friendCount;
    }

    /**
     * Get friendCount
     *
     * @return integer 
     */
    public function getFriendCount()
    {
        return $this->friendCount;
    }
    
	/**
     * Set idolCount
     *
     * @param integer $idolCount
     */
    public function setIdolCount($idolCount)
    {
        if ($idolCount < 0) $idolCount = 0;
    	$this->idolCount = $idolCount;
    }

    /**
     * Get idolCount
     *
     * @return integer 
     */
    public function getIdolCount()
    {
        return $this->idolCount;
    }
    
	/**
     * Add visits
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Visit $visits
     */
    public function addVisit(\Dodici\Fansworld\WebBundle\Entity\Visit $visits)
    {
        $visits->setTarget($this);
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
     * Set photoVisitCount
     *
     * @param integer $photoVisitCount
     */
    public function setPhotoVisitCount($photoVisitCount)
    {
        $this->photoVisitCount = $photoVisitCount;
    }

    /**
     * Get photoVisitCount
     *
     * @return integer 
     */
    public function getPhotoVisitCount()
    {
        return $this->photoVisitCount;
    }
    
	/**
     * Set videoVisitCount
     *
     * @param integer $videoVisitCount
     */
    public function setVideoVisitCount($videoVisitCount)
    {
        $this->videoVisitCount = $videoVisitCount;
    }

    /**
     * Get videoVisitCount
     *
     * @return integer 
     */
    public function getVideoVisitCount()
    {
        return $this->videoVisitCount;
    }
    
    /**
     * Get a field value 
     */
    public function getFieldValue($fieldname)
    {
        return $this->{'get'.$fieldname}();
    }
}