<?php

namespace Kaltura\APIBundle\Services;

use Kaltura\Client\Configuration as KalturaConfiguration;
use Kaltura\Client\Client as KalturaClient;
use Kaltura\Client\Enum\SessionType as KalturaSessionType;
use Kaltura\Client\ApiException;
use Kaltura\Client\ClientException;

/**
 * Service interface to Kaltura API client
 */
class Kaltura
{
    protected $client;
    protected $partnerid;
    protected $subpartnerid;
    protected $usersecret;
    protected $adminsecret;
    protected $username;

    function __construct($partnerid, $subpartnerid, $usersecret, $adminsecret, $username)
    {
        // init kaltura configuration
        $config = new KalturaConfiguration($partnerid);
        
        // init kaltura client
        $this->client = new KalturaClient($config);
        
        $this->partnerid = $partnerid;
        $this->subpartnerid = $subpartnerid;
        $this->usersecret = $usersecret;
        $this->adminsecret = $adminsecret;
        $this->username = $username;
    }

    /**
     * Get kaltura session
     * @param boolean $admin
     */
    public function getKs($admin = true)
    {
        // generate session
        $ks = $this->client->generateSession(
            $admin ? $this->adminsecret : $this->usersecret, 
            $this->username,
            $admin ? KalturaSessionType::ADMIN : KalturaSessionType::USER,
            $this->partnerid
        );
        
        return $ks;
    }
    
    /**
     * Get client
     * @param boolean $admin
     */
    public function getClient($admin = true)
    {
        return $this->client->setKs($this->getKs($admin));
    }
    
    public function getPartnerId()
    {
        return $this->partnerid;
    }
    
    public function getSubPartnerId()
    {
        return $this->subpartnerid;
    }
    
    public function getAdminSecret()
    {
        return $this->adminsecret;
    }
    
    public function getUserSecret()
    {
        return $this->usersecret;
    }
}