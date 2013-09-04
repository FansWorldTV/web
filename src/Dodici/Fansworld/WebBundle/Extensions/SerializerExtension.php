<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Serializer\Serializer;

class SerializerExtension extends \Twig_Extension
{
    protected $serializer;

    function __construct(Serializer $serializer) {
        $this->serializer = $serializer;
    }

    public function getGlobals() {
        return array(
            'serializer' => $this->serializer
        );
    }

    public function getName()
    {
        return 'serializer';
    }
}