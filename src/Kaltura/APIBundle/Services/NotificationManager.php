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
    protected $kaltura;
    protected $logger;
    protected $em;

    function __construct(Kaltura $kaltura, $logger, $em)
    {
        $this->kaltura = $kaltura;
        $this->logger = $logger;
        $this->em = $em;
    }

    public function process(Request $request) 
    {
        $notification_params = $request->request->all();
        if ($this->validate($notification_params)) {
            $data = $this->parseParams($notification_params);
            
            foreach ($data as $notification_data) {
                $id = $notification_data['notification_id'];
                
                $notification = $this->exists($id);
                if (!$notification) {
                    $this->save($id, $notification_data);
                    // TODO: do something with data
                } else {
                    $this->log('This notification ('. $id .') was already processed ('.$notification->getId().')');
                }
            }
                    
        } else {
            throw new \Exception('Invalid Kaltura notification signature');
        }
    }

    public function save($id, $data)
    {
        // TODO: persist to db
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
        $id = $request->get('notification_id');
        $notification = $this->em->getRepository('KalturaAPIBundle:Notification')->findOneByExternal($id);
        return $exists;
    }
    
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
        if(md5($admin_secret . $str) == $notification_params['sig']) {
            return true;
        } else {
            return false;
        }
    }
    
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