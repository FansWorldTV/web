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
    	$video->setViewCount($video->getViewCount() + 1);
    	$this->em->persist($video);
    	$this->em->flush();
    	if ($video->getYoutube()) {
    		return sprintf('http://www.youtube.com/embed/%1$s?autoplay=1&wmode=transparent', $video->getYoutube());
    	} elseif ($video->getVimeo()) {
    	    return sprintf('http://player.vimeo.com/video/%1$s', $video->getVimeo());
    	} else {
    		return sprintf($this->videoplayerbaseurl, $video->getStream());
    	}
    }
}