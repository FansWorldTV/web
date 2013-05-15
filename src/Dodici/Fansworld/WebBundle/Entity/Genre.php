<?php
namespace Dodici\Fansworld\WebBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Dodici\Fansworld\WebBundle\Entity\Genre
 *
 * @ORM\Table(name="genre")
 * @ORM\Entity(repositoryClass="Dodici\Fansworld\WebBundle\Model\GenreRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Genre implements Translatable
{
    /**
     * @var bigint $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     */
    private $id;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=false)
     *
     */
    private $title;

    /**
     * @Gedmo\Slug(fields={"title"}, unique=true)
     * @ORM\Column(length=250)
     */
    private $slug;

    /**
     * @var Parent
     *
     * @ORM\ManyToOne(targetEntity="Genre")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Genre", mappedBy="genre", cascade={"remove", "persist"}, orphanRemoval="true")
     */
    protected $children;

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

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->getTitle();
    }

    public function getType()
    {
        if ($this->parent) {
            return 'subgenre';
        } else {
            return 'genre';
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
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set parent
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Genre $genre
     */
    public function setParent(\Dodici\Fansworld\WebBundle\Entity\Genre $genre)
    {
        $this->parent = $genre;
    }

    /**
     * Get parent
     *
     * @return Dodici\Fansworld\WebBundle\Entity\Genre
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add children
     *
     * @param Dodici\Fansworld\WebBundle\Entity\Genre $genre
     */
    public function addChildren(\Dodici\Fansworld\WebBundle\Entity\Genre $genre)
    {
        $this->children[] = $genre;
    }

    /**
     * Get children
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }
}