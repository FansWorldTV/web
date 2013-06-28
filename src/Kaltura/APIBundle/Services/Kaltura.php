<?php

namespace Kaltura\APIBundle\Services;

use Kaltura\Client\Configuration as KalturaConfiguration;
use Kaltura\Client\Client as KalturaClient;
use Kaltura\Client\Enum\SessionType as KalturaSessionType;
use Kaltura\Client\ApiException;
use Kaltura\Client\ClientException;
use Kaltura\Client\Type\MediaEntry;
use Kaltura\Client\Type\User as KalturaUser;
use Kaltura\Client\Plugin\Metadata\Enum\MetadataObjectType;
use Kaltura\Client\Plugin\Metadata\MetadataPlugin;
use Kaltura\Client\Type\UploadedFileTokenResource;
use Kaltura\Client\Enum\MediaType;

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
    protected $patterns;
    protected $apiurl;
    protected $metaid;
    protected $metasiteval;
    protected $metauserval;

    function __construct($partnerid, $subpartnerid, $usersecret, $adminsecret, $username, $urlpattern, $apiurl, $metaid, $metasiteval, $metauserval)
    {
        // init kaltura configuration
        $config = new KalturaConfiguration($partnerid);
        
        // init kaltura client
        $this->client = new KalturaClient($config);
        
        $this->patterns = array(
            'base' => $urlpattern,
            'progressive' => '/format/url/flavorId/%3$s/video.%4$s',
            'hls' => '/format/applehttp/protocol/http/a.m3u8',
            'rtmp' => '/format/rtmp'
        );
        
        $this->partnerid = $partnerid;
        $this->subpartnerid = $subpartnerid;
        $this->usersecret = $usersecret;
        $this->adminsecret = $adminsecret;
        $this->username = $username;
        $this->apiurl = $apiurl;
        $this->metaid = $metaid;
        $this->metasiteval = $metasiteval;
        $this->metauserval = $metauserval;
    }

    /**
     * Get kaltura session
     * @param boolean $admin
     */
    public function getKs($admin = true, $userId = null)
    {
        $username = null;
        if (!$admin && $userId) {
            $user = $this->getUser($userId);
            if (!$user) throw new \Exception('Failed creating user');
            $username = $user->id;
        }

        // generate session
        $ks = $this->client->generateSession(
            $this->adminsecret,
            $username ?: $this->username,
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
    
    public function addEntryFromToken($token, $name)
    {
        $uploadedresource = new UploadedFileTokenResource();
        $uploadedresource->token = $token;
        
        $entry = new MediaEntry();
        $entry->mediaType = MediaType::VIDEO;
        $entry->name = $name;
        $es = $this->getClient()->getMediaService();
        $entry = $es->add($entry);
        if ($entry->id) {
            $entry = $es->addContent($entry->id, $uploadedresource);
            if ($entry->id) return $entry->id;
            else return false;
        } else {
            return false;
        }
    }
    
    public function getUploadToken()
    {
        $uts = $this->getClient()->getUploadTokenService();
        $token = $uts->add();
        if ($token) return $token->id;
        return false;
    }

    public function getUser($userId)
    {
        $this->client->setKs($this->getKs());
        $user = null;
        try {
            $user = $this->client->user->get($this->getUsername($userId));
        } catch(\Exception $e) {

        }
        if (!$user) {
            $user = new KalturaUser();
            $user->id = $this->getUsername($userId);
            $user = $this->client->user->add($user);
        }
        return $user;
    }
    
    /**
     * Return an entry's streams in its available flavors
     * @param string $entryId
     */
    public function streams($entryId, $protocol='progressive')
    {
        
        switch ($protocol) {
            case 'rtmp':
            case 'hls':
                $pattern = $this->patterns['base'].$this->patterns[$protocol];
                return sprintf($pattern, $this->partnerid, $entryId);
                break;
            case 'progressive':
                $fas = $this->getClient()->getFlavorAssetService();
                $flavors = $fas->getFlavorAssetsWithParams($entryId);
                $pattern = $this->patterns['base'].$this->patterns[$protocol];
                
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
                            $flavorId = $asset->id;
                            $flavorParamsId = $asset->flavorParamsId;
                            $partnerId = $asset->partnerId;
                            $size = $asset->size;
                            
                            $name = $params->name;
                            $desc = $params->description;
                            
                            $streamurl = sprintf($pattern, $partnerId, $entryId, $flavorId, $ext);
                            
                            $streams[] = array(
                                'url' => $streamurl,
                                'format' => array(
                                    'id' => $flavorParamsId,
                                    'fid' => $flavorId,
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
                break;
            default:
                throw new \Exception('Invalid stream protocol: '.$protocol);
                break;
        }
        
        return $streams;
    }
    
    public function setSiteMetadata($entryId)
    {
        return $this->setMeta($entryId, $this->metaid, $this->metasiteval);
    }
    
    public function setUserMetadata($entryId)
    {
        return $this->setMeta($entryId, $this->metaid, $this->metauserval);
    }
    
    /**
     * Get client
     * @param boolean $admin
     */
    public function getClient()
    {
        if (!$this->client->getKs()) {
            $this->client->setKs($this->getKs());
        }
        return $this->client;
    }

    public function init($admin = true, $userId = null)
    {
        $this->client->setKs($this->getKs($admin, $userId));
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

    protected function getUsername($userId)
    {
        return 'fw-user-'.$userId;
    }
    
    protected function setMeta($entryId, $metaid, $xml)
    {
        $metadataPlugin = MetadataPlugin::get($this->getClient());
        $ms = $metadataPlugin->metadata;
        try {
            $r = $ms->add($metaid, MetadataObjectType::ENTRY, $entryId, $xml);
            return $r;
        } catch(\Exception $e) {
            if ($e->getCode() == 'METADATA_ALREADY_EXISTS') {
                $m = $e->getMessage();
                if (strpos($m, '[') !== false) {
                    $oldmetaid = substr($m, strpos($m, '[') + 1);
                    $oldmetaid = substr($oldmetaid, 0, strpos($oldmetaid, ']'));
                    if ($oldmetaid) {
                        $r = $ms->update($oldmetaid, $xml);
                        return $r;
                    }
                }
            }
        }
        return false;
    }
}