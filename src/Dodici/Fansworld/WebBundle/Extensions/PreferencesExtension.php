<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\Preferences;

class PreferencesExtension extends \Twig_Extension
{
    protected $preferences;

    function __construct(Preferences $preferences) {
        $this->preferences = $preferences;
    }

    public function getGlobals() {
        return array(
            'preferences' => $this->preferences
        );
    }

    public function getName()
    {
        return 'preferences';
    }

}