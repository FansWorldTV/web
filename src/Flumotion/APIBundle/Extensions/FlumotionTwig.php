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
    	} else {
    		return sprintf($this->videoplayerbaseurl, $video->getStream());
    	}
    }
    
	/**
     * @throws \RuntimeException
     * @param string $id
     * @return mixed|null|string
     */
    public function getYoutubeMetadata($id)
    {
        if (!$id) {
            return null;
        }

        $url = sprintf('http://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=%s&format=json', $id);
        $metadata = @file_get_contents($url);

        if (!$metadata) {
            throw new \RuntimeException('Unable to retrieve youtube video information for :' . $url);
        }

        $metadata = json_decode($metadata, true);

        if (!$metadata) {
            throw new \RuntimeException('Unable to decode youtube video information for :' . $url);
        }

        return $metadata;
    }
    
    public function getYoutubeId($youtube)
    {
    	$youtube = str_replace(
		array('http://','www.youtube.com/watch?v=','youtu.be/','www.youtube.com/v/'), 
		array('','','',''), 
		$youtube);
		if (strpos($youtube, '&') !== false) {
			$youtube = substr($youtube, 0, strpos($youtube, '&'));
		}
		return $youtube;
    }
}