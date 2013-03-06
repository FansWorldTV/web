<?php
namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\VideoPlaylist;

class VideoPlaylistExtension extends \Twig_Extension
{
    protected $videoplaylist;

    function __construct(VideoPlaylist $videoplaylist) {
        $this->videoplaylist = $videoplaylist;
    }

    public function getGlobals() {
        return array(
            'videoplaylist' => $this->videoplaylist
        );
    }

    public function getName()
    {
        return 'videoplaylist';
    }

}