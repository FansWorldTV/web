<?php
namespace Flumotion\APIBundle\Extensions;

use Dodici\Fansworld\WebBundle\Services\Visitator;
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
    protected $visitator;
    
    protected $playerbaseurl;
    protected $videoplayerbaseurl;
    protected $videoplayersmallurl;
    

    function __construct(Session $session, EntityManager $em, $api, Visitator $visitator, $playerbaseurl, $videoplayerbaseurl, $videoplayersmallurl)
    {
        $this->session = $session;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->api = $api;
        $this->visitator = $visitator;
        
        $this->playerbaseurl = $playerbaseurl;
        $this->videoplayerbaseurl = $videoplayerbaseurl;
        $this->videoplayersmallurl = $videoplayersmallurl;
    }
    
	public function getVideoPlayerUrl(Video $video, $small=false)
    {
    	$this->visitator->visit($video);
    	if ($video->getYoutube()) {
    		return sprintf('http://www.youtube.com/embed/%1$s?autoplay=1&wmode=transparent', $video->getYoutube());
    	} elseif ($video->getVimeo()) {
    	    return sprintf('http://player.vimeo.com/video/%1$s', $video->getVimeo());
    	} else {
    		return $this->rawPlayerUrl($video->getStream(), $small=false);
    	}
    }
    
    public function rawPlayerUrl($id, $small=false)
    {
        return sprintf($small ? $this->videoplayersmallurl : $this->videoplayerbaseurl, $id);
    }
}