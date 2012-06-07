<?php

namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\Badge
 * 
 * Achievement badge
 *
 * @ORM\Table(name="badge")
 * @ORM\Entity
 */
class Badge implements Translatable
{
    const TYPE_IDOLSHIP = 1;
    const TYPE_TEAMSHIP = 2;
    const TYPE_FRIENDSHIP = 3;
    const TYPE_VIDEO = 4;
    const TYPE_PHOTO = 5;
    const TYPE_EVENTSHIP = 6;
    const TYPE_CONTESTPARTICIPANT = 7;
    const TYPE_QUIZANSWER = 8;
    const TYPE_COMMENT = 9;
    const TYPE_PROFILEVIEWS = 10;

	public static function getTypes()
    {
    	return array(
    		self::TYPE_IDOLSHIP => 'Ídolos seguidos',
            self::TYPE_TEAMSHIP => 'Equipos seguidos',
            self::TYPE_FRIENDSHIP => 'Usuarios seguidos',
            self::TYPE_VIDEO => 'Vídeos subidos',
            self::TYPE_PHOTO => 'Fotos subidas',
            self::TYPE_EVENTSHIP => 'Check-ins',
            self::TYPE_CONTESTPARTICIPANT => 'Participaciones Concursos',
            self::TYPE_QUIZANSWER => 'Respuestas Encuestas',
            self::TYPE_COMMENT => 'Comentarios',
            self::TYPE_PROFILEVIEWS => 'Vistas perfil+fotos+videos',
    	);
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
     * @var string $title
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=false)
     */
    private $title;

    /**
     * @var text $content
     * @Gedmo\Translatable
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;
    
    /**
     * @var integer $type
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;
    
    /**
     * @ORM\OneToMany(targetEntity="BadgeStep", mappedBy="badge", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $badgesteps;
        
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

	public function __toString() {
		return $this->getTitle();
	}
	
	public function getTypeName() {
	    return self::getTypes($this->getType());
	}

    public function __construct()
    {
        $this->badgesteps = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
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
     * Add badgesteps
     *
     * @param Dodici\Fansworld\WebBundle\Entity\BadgeStep $badgesteps
     */
    public function addBadgeStep(\Dodici\Fansworld\WebBundle\Entity\BadgeStep $badgesteps)
    {
        $this->badgesteps[] = $badgesteps;
    }
    public function addBadgeSteps(\Dodici\Fansworld\WebBundle\Entity\BadgeStep $badgesteps)
    {
        $this->addBadgeStep($badgesteps);
    }

    /**
     * Get badgesteps
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getBadgesteps()
    {
        return $this->badgesteps;
    }
    public function setBadgesteps($badgesteps)
    {
        $this->badgesteps = $badgesteps;
    }
}