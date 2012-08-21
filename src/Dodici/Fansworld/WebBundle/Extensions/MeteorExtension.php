<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\Meteor;

class MeteorExtension extends \Twig_Extension
{
    protected $meteor;

    function __construct(Meteor $meteor) {
        $this->meteor = $meteor;
    }

    public function getGlobals() {
        return array(
            'meteor' => $this->meteor
        );
    }

    public function getName()
    {
        return 'meteor';
    }

}