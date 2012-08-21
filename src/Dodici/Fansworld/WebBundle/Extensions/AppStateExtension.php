<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\AppState;

class AppStateExtension extends \Twig_Extension
{
    protected $appState;

    function __construct(AppState $appState) {
        $this->appState = $appState;
    }

    public function getGlobals() {
        return array(
            'appstate' => $this->appState
        );
    }

    public function getName()
    {
        return 'appstate';
    }

}