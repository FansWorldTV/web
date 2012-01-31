<?php
namespace Flumotion\APIBundle\Extensions;

use Flumotion\APIBundle\Extensions\FlumotionTwig;

class FlumotionTwigExtension extends \Twig_Extension
{
    protected $flumotionTwig;

    function __construct(FlumotionTwig $flumotionTwig) {
        $this->flumotionTwig = $flumotionTwig;
    }

    public function getGlobals() {
        return array(
            'flumotiontwig' => $this->flumotionTwig
        );
    }

    public function getName()
    {
        return 'flumotiontwig';
    }

}