<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\Search;

class SearchExtension extends \Twig_Extension
{
    protected $search;

    function __construct(Search $search) {
        $this->search = $search;
    }

    public function getGlobals() {
        return array(
            'search' => $this->search
        );
    }

    public function getName()
    {
        return 'search';
    }

}