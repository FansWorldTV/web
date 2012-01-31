<?php
namespace Flumotion\APIBundle\Builder;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\DoctrineBundle\Registry;

use Dodici\WebBundle\Entity\Blog;
use Dodici\WebBundle\Entity\BlogTime;

class RequestBuilder {
    /** @var \Symfony\Bundle\DoctrineBundle\Registry */
    private $orm;
    
    private $api_url;
    private $api_key;
    private $api_secret;
    private $request;

    public function __construct($orm, $api_url, $api_key, $api_secret)
    {
        $this->orm = $orm;
        $this->api_url = $api_url;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->request = Request::createFromGlobals();
    }
    
    private function getSignature()
    {
    	$ts = $this->getTimestamp();
    	$nonce = rand(10000000, 99999999);
    	$sig = 'api_key=%1$s&api_nonce=%2$s&api_referrer=%3$s&api_timestamp=%4$s%5$s';
    	$sig = sprintf($sig, 
    		$this->api_key,
    		$nonce,
    		$this->request->getHost(),
    		$ts,
    		$this->api_secret
    	);
    	$sig = sha1($sig);
    	
    	$str = sprintf('api_referrer=%1$s&api_nonce=%2$s&api_timestamp=%3$s&api_key=%4$s&api_signature=%5$s',
    		$this->request->getHost(),
    		$nonce,
    		$ts,
    		$this->api_key,
    		$sig
    	);
    	return $str;
    }
    
    private function getCurl($url, $parameters=array(), $management=false)
    {
    	$rurl=$this->api_url.$url;
    	if ($management) $rurl = str_replace('api.', 'bo.', $rurl);
    	$ch = curl_init($rurl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if ($parameters) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		}
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
    }
    
	private function intToHour($hourint)
    {
    	return (sprintf('%02d',floor($hourint/100)).':'.sprintf('%02d',($hourint%100)));
    }

    private function getTimestamp()
    {
		return json_decode($this->getCurl('/get_server_time'));
    }
    
	public function getStream($id)
    {
        return json_decode($this->getCurl('/pods/'.$id.'/streams'));
    }
    
	public function getAudios($id)
    {
        return json_decode($this->getCurl('/channels/'.$id.'/audios'));
    }
    
    public function createMetadata($name, $channel, \DateTime $date)
    {
    	$tz = new \DateTimeZone('UTC');
    	$date = $date->setTimezone($tz);
    	$data = array(
    		'media_type' => 'audio',
    		'filename' => $name.'.mp3',
    		'name' => $name,
    		'channel' => $channel,
    		'publish_date' => $date->format('Y-m-d H:i:s'),
    		'broadcast_date' => $date->format('Y-m-d H:i:s')
    	);
    	return ($this->getCurl('/remote/metadata?response=id&'.$this->getSignature(),
    	$data, true));
    }
    
    public function getCalendar($blogs)
    {
    	$cal = "BEGIN:VCALENDAR\n";
		$cal .= "VERSION:2.0\n";
		$cal .= "PRODID:-//Dodici Digital//Web Bundle//ES\n";
		$cal .= "METHOD:PUBLISH\n";
    	
    	$tz = new \DateTimeZone('UTC');
    	
    	foreach ($blogs as $blog) {
	    	foreach ($blog->getBlogtimes() as $bt) {
	    		$now = new \DateTime('now');
		    	$start = new \DateTime($this->intToHour($bt->getHourfrom()));
		    	$end = new \DateTime($this->intToHour($bt->getHourto()));
		    	
		    	$now = $now->setTimezone($tz);
		    	$start = $start->setTimezone($tz);
		    	$end = $end->setTimezone($tz);
		    	
		    	switch ($bt->getWeektype()) {
		    		case BlogTime::TYPE_ALLWEEK: $byday = 'MO,TU,WE,TH,FR,SA,SU'; break;
		    		case BlogTime::TYPE_WEEKDAYS: $byday = 'MO,TU,WE,TH,FR'; break;
		    		case BlogTime::TYPE_WEEKEND: $byday = 'SA,SU'; break;
		    		case BlogTime::TYPE_SATURDAYS: $byday = 'SA'; break;
		    		case BlogTime::TYPE_SUNDAYS: $byday = 'SU'; break;
		    	}
		    	
				$cal .= "BEGIN:VEVENT\n";
				$cal .= "CLASS:PUBLIC\n";
				$cal .= "DTSTAMP:".$now->format('Ymd\\THis\\Z')."\n";
				$cal .= "DTSTART:".$start->format('Ymd\\THis\\Z')."\n";
				$cal .= "DTEND:".$end->format('Ymd\\THis\\Z')."\n";
				$cal .= "UID:".uniqid(rand(1,10000), true)."\n";
				$cal .= "SUMMARY:".$blog->getTitle()." - %Y-%m-%d - %H:%M\n";
				$cal .= "RRULE:FREQ=WEEKLY;BYDAY=".$byday."\n";
				$cal .= "END:VEVENT\n";
	    	}
    	}
		
		$cal .= "END:VCALENDAR\n";
		
		return $cal;
    }
    
	public function putPlaceholders($blogs)
    {
    	$tz = new \DateTimeZone('UTC');
    	foreach ($blogs as $blog) {
	    	$channelname = $blog->getSite()->getTitle() . '|' . $blog->getTitle();
    		foreach ($blog->getBlogtimes() as $bt) {
		    	$starth = $this->intToHour($bt->getHourfrom());
		    	$endh = $this->intToHour($bt->getHourto());
	    		switch ($bt->getWeektype()) {
		    		case BlogTime::TYPE_ALLWEEK: $byday = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday'); break;
		    		case BlogTime::TYPE_WEEKDAYS: $byday = array('monday','tuesday','wednesday','thursday','friday'); break;
		    		case BlogTime::TYPE_WEEKEND: $byday = array('saturday','sunday'); break;
		    		case BlogTime::TYPE_SATURDAYS: $byday = array('saturday'); break;
		    		case BlogTime::TYPE_SUNDAYS: $byday = array('sunday'); break;
		    	}
		    	
		    	foreach ($byday as $bd) {
			    	$start = new \DateTime('next '.$bd.' '.$starth);
			    	$start = $start->setTimezone($tz);
			    	$end = new \DateTime('next '.$bd.' '.$endh);
			    	$end = $end->setTimezone($tz);
			    	$filename = $blog->getTitle() . ' - ' . $start->format('Y-m-d - H:i');
			    	$this->createMetadata($filename, $channelname, $start);
		    	}
    		}
    	}
    }
}