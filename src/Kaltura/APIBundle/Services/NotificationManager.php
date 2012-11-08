<?php

namespace Kaltura\APIBundle\Services;

use Kaltura\APIBundle\Services\Kaltura as Kaltura;
use Kaltura\APIBundle\Entity\Notification;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kaltura server notifications parser
 */
class NotificationManager
{
    // The entry_add notification is being sent to notify that a new entry has been successfully added and ready for use.
    const ENTRY_ADD	= 'entry_add';
    // The entry_block notification is sent to notify that an entry has been blocked by a moderator or admin user.
    const ENTRY_BLOCK = 'entry_block';
    // The entry_delete notification is being sent to notify that an entry has been deleted.
    const ENTRY_DELETE = 'entry_delete';
    // The entry_update notification is being sent to notify that an entry has been updated.
    const ENTRY_UPDATE = 'entry_update';
    // The entry_update_moderation notification is being sent to notify that the moderation status of an entry has been updated.
    const ENTRY_UPDATE_MODERATION = 'entry_update_moderation';
    // The entry_update_thumbnail notification is being sent to notify that thumbnail of an entry has been updated.
    const ENTRY_UPDATE_THUMBNAIL = 'entry_update_thumbnail';
    // The entry_update_permissions notification is being sent to notify that the privacy settings of an entry have changed.
    const ENTRY_UPDATE_PERMISSIONS = 'entry_update_permissions';
    // The user_add notification is being sent to notify that a specific user was added to the Kaltura DB.
    const USER_ADD = 'user_add';
    // The user_banned notification is being sent to notify that a specific user was banned.
    const USER_BANNED = 'user_banned';
    
    protected $kaltura;
    protected $logger;
    protected $em;

    function __construct(Kaltura $kaltura, $logger, $em)
    {
        $this->kaltura = $kaltura;
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * Validate the signature, save notification if new, call processing callback
     * @param Request $request
     */
    public function process(Request $request) 
    {
        $notification_params = $request->request->all();
        if ($this->validate($notification_params)) {
            $data = $this->parseParams($notification_params);
            
            foreach ($data as $notification_data) {
                $id = $notification_data['notification_id'];
                
                $notification = $this->exists($id);
                if (!$notification) {
                    if ($this->callback($notification_data)) {
                        $this->save($id, $notification_data);
                    }
                } else {
                    $this->log('This notification ('. $id .') was already processed ('.$notification->getId().')');
                }
            }
                    
        } else {
            throw new \Exception('Invalid Kaltura notification signature');
        }
    }
    
    /**
     * Override in your bundle
     * @param array $notification_data
     */
    public function callback($notification_data)
    {
        return true;
    }

    /**
     * Persist the notification
     * @param int $id
     * @param array $data
     */
    public function save($id, $data)
    {
        $notification = new Notification();
        $notification->setExternal($id);
        $notification->setData($data);
        
        $this->em->persist($notification);
        $this->em->flush();
        
        $this->log('Notification ('. $id .') saved as local id ('.$notification->getId().')');
    }
    
    /**
     * Return whether we have already processed and recorded the notification 
     * @param int $id
     */
    public function exists($id)
    {
        $notification = $this->em->getRepository('KalturaAPIBundle:Notification')->findOneByExternal($id);
        return $notification;
    }
    
    /**
     * Output to logger
     * @param string $message
     */
    public function log($message)
    {
        $this->logger->info('[KALTURA-NOT] '.$message);
    }
    
    /**
     * Validates the signature of a request
     * @param array $notification_params
     */
    private function validate($notification_params) 
    {
        $adminsecret = $this->kaltura->getAdminSecret();
        
        if(!count($notification_params)) return false;        
        
        ksort($notification_params);
        $str = "";
        $valid_params = array();
        if (key_exists('signed_fields', $notification_params)) {
            $valid_params = explode(',', $notification_params['signed_fields']);      
        }
        foreach ($notification_params as $k => $v) {
    	    if ( $k == "sig" ) continue;
    	    if (!in_array($k, $valid_params) && count($valid_params) > 1 &&!$notification_params['multi_notification']) {
        	    if ( $k != 'multi_notification' && $k != 'number_of_notifications') {
        		    continue;
        	    }
    	    }
    	    $str .= $k.$v;
        }
        if(md5($adminsecret . $str) == $notification_params['sig']) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Parse multinotifications, etc
     * @param array $data
     */
    private function parseParams($data)
    {
        $res = array();
        if(isset ( $data["multi_notification"] ) &&   $data["multi_notification"] === "true"){
            foreach($data as $name => $value){
                $parts = array();
                $num = null;
                
                $match = preg_match ( "/^(not[^_]*)_(.*)$/" , $name , $parts );
                if ( ! $match ) continue;
                $not_name_parts = isset($parts[1]) ? $parts[1] : null;
                $not_property = isset($parts[2]) ? $parts[2] : null;
                
                if ($not_name_parts) $num = ( int )str_replace('not','',$not_name_parts);
                if ($num && $not_property) $res[$num][$not_property] = $value;
            }
        } else {
            $res = array($data);
        }
        return $res;
    }
}