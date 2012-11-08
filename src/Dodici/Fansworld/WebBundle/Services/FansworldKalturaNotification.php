<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Kaltura\Client\Enum\EntryStatus;
use Kaltura\APIBundle\Services\NotificationManager;
use Kaltura\APIBundle\Services\Kaltura;

/**
 * Process Kaltura notifications in Fansworld
 */
class FansworldKalturaNotification extends NotificationManager
{
    protected $videouploader;
    
    function __construct(Kaltura $kaltura, $logger, $em, $videouploader)
    {
        parent::__construct($kaltura, $logger, $em);
        $this->videouploader = $videouploader;
    }
    
    /**
     * Processing callback override
     * @param array $notification_data
     */
    public function callback($notification_data)
    {
        switch ($notification_data['notification_type']) {
            case (self::ENTRY_UPDATE):
                $status = $notification_data['status'];
                $thumb = $notification_data['thumbnail_url'];
                $entryid = $notification_data['entry_id'];
                $length = $notification_data['length_in_msecs'];
                
                if ($status == EntryStatus::READY) {
                    // if status is finished, send processing ok
                    
                    $video = $this->em->getRepository('DodiciFansworldWebBundle:Video')->findOneByStream($entryid);
                    if ($video) {
                        return $this->videouploader->process($video, $thumb, $length);
                    }
                }
                
                break;
        }
        
        return true;
    }
}