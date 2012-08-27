<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\Fanmaker;

class FanmakerExtension extends \Twig_Extension
{
    protected $fanmaker;

    function __construct(Fanmaker $fanmaker) {
        $this->fanmaker = $fanmaker;
    }

    public function getGlobals() {
        return array(
            'fanmaker' => $this->fanmaker
        );
    }

    public function getName()
    {
        return 'fanmaker';
    }

}