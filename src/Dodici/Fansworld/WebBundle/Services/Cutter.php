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
    protected $request;
    protected $em;
    protected $appstate;

    function __construct(EntityManager $em, $appstate)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->appstate = $appstate;
    }

    public function cutImage(array $options) {
        $imagine = new Imagine();
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $options['tempFile'];
        $format = $this->get('appmedia')->getType($path);

        $imageStream = $imagine->open($path);

        if ($options['cropW'] && $options['cropH']) {
            $imageStream = $imageStream
            ->crop(new Point($options['cropX'], $options['cropY']), new Box($options['cropW'], $options['cropH']));
        }

        $metaData = array('filename' => $options['originalFile']);
        return $this->get('appmedia')->createImageFromBinary($imageStream->get($format), $metaData);
    }
}