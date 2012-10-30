<?php

namespace Dodici\Fansworld\WebBundle\Services;

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
    protected $usersecret;
    protected $adminsecret;
    protected $username;

    function __construct($partnerid, $usersecret, $adminsecret, $username)
    {
        // init kaltura configuration
        $config = new KalturaConfiguration($partnerid);
        
        // init kaltura client
        $this->client = new KalturaClient($config);
        
        $this->partnerid = $partnerid;
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
}