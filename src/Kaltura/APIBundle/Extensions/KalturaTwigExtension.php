<?php
namespace Kaltura\APIBundle\Extensions;

use Kaltura\APIBundle\Services\KalturaTwig;

class KalturaTwigExtension extends \Twig_Extension
{
    protected $kalturaTwig;

    function __construct(KalturaTwig $kalturaTwig) {
        $this->kalturaTwig = $kalturaTwig;
    }

    public function getGlobals() {
        return array(
            'kalturatwig' => $this->kalturaTwig
        );
    }

    public function getName()
    {
        return 'kalturatwig';
    }

}