<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\AppFacebook;

class AppFacebookExtension extends \Twig_Extension
{
    protected $appFacebook;

    function __construct(AppFacebook $appFacebook) {
        $this->appFacebook = $appFacebook;
    }

    public function getGlobals() {
        return array(
            'appfacebook' => $this->appFacebook
        );
    }

    public function getName()
    {
        return 'appfacebook';
    }

}