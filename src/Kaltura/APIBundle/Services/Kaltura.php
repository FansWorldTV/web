<?php

namespace Kaltura\APIBundle\Services;

use Kaltura\Client\Configuration as KalturaConfiguration;
use Kaltura\Client\Client as KalturaClient;
use Kaltura\Client\Enum\SessionType as KalturaSessionType;
use Kaltura\Client\ApiException;
use Kaltura\Client\ClientException;
use Kaltura\Client\Type\MediaEntry;

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
    protected $urlpattern;
    protected $apiurl;

    function __construct($partnerid, $subpartnerid, $usersecret, $adminsecret, $username, $urlpattern, $apiurl)
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
        $this->urlpattern = $urlpattern;
        $this->apiurl = $apiurl;
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
     * Return an entry's data
     * @param string $entryId
     */
    public function getEntry($entryId)
    {
        $es = $this->getClient()->getMediaService();
        $entry = $es->get($entryId);
        
        return $entry;
    }
    
    /**
     * Update an entry's fields
     * @param string $entryId
     * @param array $fields
     */
    public function updateEntry($entryId, array $fields)
    {
        $entry = new MediaEntry();
        foreach ($fields as $k => $v) {
            $entry->$k = $v;
        }
        $es = $this->getClient()->getMediaService();
        $updatedentry = $es->update($entryId, $entry);
        return $updatedentry;
    }
    
    /**
     * Return an entry's streams in its available flavors
     * @param string $entryId
     */
    public function streams($entryId)
    {
        $fas = $this->getClient()->getFlavorAssetService();
        $flavors = $fas->getFlavorAssetsWithParams($entryId);
        
        $ext = 'mp4';
        
        $streams = array();
        
        if ($flavors) {
            foreach ($flavors as $flavor) {
                $asset = $flavor->flavorAsset;
                
                if ($asset) {
                    $params = $flavor->flavorParams;
                    $width = $asset->width;
                    $height = $asset->height;
                    $bitrate = $asset->bitrate;
                    $flavorId = $asset->flavorParamsId;
                    $partnerId = $asset->partnerId;
                    $size = $asset->size;
                    
                    $name = $params->name;
                    $desc = $params->description;
                    
                    $streamurl = sprintf($this->urlpattern, $partnerId, $entryId, $flavorId, $ext);
                    
                    $streams[] = array(
                        'url' => $streamurl,
                        'format' => array(
                            'id' => $flavorId,
                            'name' => $name,
                            'description' => $desc
                        ),
                        'bitrate' => $bitrate,
                        'size' => $size,
                        'width' => $width,
                        'height' => $height
                    );
                }
            }
        }
        
        return $streams;
    }
    
    /**
     * Get client
     * @param boolean $admin
     */
    public function getClient($admin = true)
    {
        if (!$this->client->getKs()) {
            $this->client->setKs($this->getKs($admin));
        }
        return $this->client;
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
    
    public function getApiUrl()
    {
        return $this->apiurl;
    }
}