<?php
namespace Dodici\Fansworld\WebBundle\Services;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;

class Cutter
{
    protected $appmedia;

    public function __construct($appmedia)
    {
        $this->appmedia = $appmedia;
    }

    public function cutImage(array $options) {
        $imagine = new Imagine();
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $options['tempFile'];
        $format = $this->appmedia->getType($path);

        $imageStream = $imagine->open($path);

        if ($options['cropW'] && $options['cropH']) {
            $imageStream = $imageStream
            ->crop(new Point($options['cropX'], $options['cropY']), new Box($options['cropW'], $options['cropH']));
        }

        $metaData = array('filename' => $options['originalFile']);
        return $this->appmedia->createImageFromBinary($imageStream->get($format), $metaData);
    }
}