<?php
namespace Flumotion\APIBundle\Extensions;

use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Dodici\Fansworld\WebBundle\Entity\Video;

class FlumotionTwig
{
    protected $session;
    protected $request;
    protected $em;
    protected $api;
    
    protected $playerbaseurl;
    protected $videoplayerbaseurl;
    

    function __construct(Session $session, EntityManager $em, $api, $playerbaseurl, $videoplayerbaseurl)
    {
        $this->session = $session;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->api = $api;
        $this->playerbaseurl = $playerbaseurl;
        $this->videoplayerbaseurl = $videoplayerbaseurl;
    }
    
	public function getVideoPlayerUrl(Video $video)
    {
    	if ($video->getYoutube()) {
    		return sprintf('http://www.youtube.com/v/%1$s?autoplay=1', $video->getYoutube());
    	} else {
    		return sprintf($this->videoplayerbaseurl, $video->getStream());
    	}
    }
}