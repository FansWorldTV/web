<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Extensions\AppMedia;

class AppMediaExtension extends \Twig_Extension
{
    protected $appMedia;

    function __construct(AppMedia $appMedia) {
        $this->appMedia = $appMedia;
    }

    public function getGlobals() {
        return array(
            'appmedia' => $this->appMedia
        );
    }

    public function getName()
    {
        return 'appmedia';
    }

}