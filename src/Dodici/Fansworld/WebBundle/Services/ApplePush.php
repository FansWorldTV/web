<?php

namespace Dodici\Fansworld\WebBundle\Services;

/**
 * Service to send data to APNS (Apple Push Notification Server)
 */
class ApplePush
{
	protected $url;
	protected $certificate;
	protected $password;

    function __construct($url, $certificate, $password)
    {
        $this->url = $url;
        $this->certificate = $certificate;
        $this->password = $password;
    }

    /**
     * Push onto the apple server
     * @param string $device - device token
     * @param mixed $message - message, string or array, to send
     * @param array $extra - extra key/value pairs to merge into json to be sent
     */
    public function send($device, $message, $extra = array())
    {
    	$ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->certificate);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->password);
    	
        $err = null; $errstr = null;
        
        $fp = stream_socket_client(
        	$this->url, $err,
        	$errstr, 3, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
        
        if (!$fp) {
        	// throw new \Exception("Failed to connect: $err $errstr");
        	return false;
        } else {
            
            $body = array(
                'aps' => array(
                    'alert' => $message,
                    'sound' => 'default'
                )
            );
            
            // Merge extra options
            $body = array_merge($body, $extra);
            
    	    // Encode the payload as JSON
            $payload = json_encode($body);
            
            // Build the binary notification
            $msg = chr(0) . pack('n', 32) . pack('H*', $device) . pack('n', strlen($payload)) . $payload;
            
            // Send it to the server
            $result = fwrite($fp, $msg, strlen($msg));
            
            // Close the connection to the server
            fclose($fp);
            
            return $result ? true : false;
        }
    }
}