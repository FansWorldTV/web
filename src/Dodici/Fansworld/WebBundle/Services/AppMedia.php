<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\Request;
use Imagine\Gd\Imagine;
use Imagine\Gd\Image;

class AppMedia
{
    protected $request;
    protected $mediapool;
    protected $manager;
    protected $absoluteaux;
    
    private $types = array(
        IMAGETYPE_GIF      => 'gif',
        IMAGETYPE_JPEG     => 'jpeg',
        IMAGETYPE_JPEG2000 => 'jpeg',
        IMAGETYPE_PNG      => 'png',
        IMAGETYPE_UNKNOWN  => 'unknown',
        IMAGETYPE_WBMP     => 'wbmp',
        IMAGETYPE_XBM      => 'xbm'
    );

    function __construct($mediapool, $manager, $absoluteaux)
    {
        $this->mediapool = $mediapool;
        $this->manager = $manager;
        $this->request = Request::createFromGlobals();
        $this->absoluteaux = $absoluteaux;
    }

    public function getImageUrl($media, $sizeFormat = 'small')
    {
        $imageUrl = null;
        
        $host = $this->request->getScheme() . '://' . $this->request->getHost();

        if ($media) {
            $mediaService = $this->mediapool;
            
            $provider = $mediaService->getProvider($media->getProviderName());

            $format = $provider->getFormatName($media, $sizeFormat);
            $imageUrl = $provider->generatePublicUrl($media, $format);
            
            return $host . $imageUrl;
        }
        
        return false;
    }
    
    /**
     * Create a Media image object from a url
     * @param string $url
     * @param array $metadata
     * 
     * @return Media
     */
    public function createImageFromUrl($url, $metadata=array())
    {
        $imagecontent = @file_get_contents($url);
        return $this->createImageFromBinary($imagecontent, $metadata);
    }
    
    /**
     * Create a Media image object from binary content
     * @param string $imagecontent
     * @param array $metadata
     * 
     * @return Media
     */
    public function createImageFromBinary($imagecontent, $metadata=array())
    {
        if ($imagecontent) {
            $tmpfile = tempnam('/tmp', 'IYT');
            file_put_contents($tmpfile, $imagecontent);
            $mediaManager = $this->manager;
            $image = new Media();
            $image->setBinaryContent($tmpfile);
            $image->setContext('default');
            $image->setProviderName('sonata.media.provider.image');
            foreach ($metadata as $key => $val) {
                $image->setMetadataValue($key,$val);
            }
            $mediaManager->save($image);
            return $image;
        } else {
            throw new \Exception('No binary image content');
        }
    }
    
    public function getAbsolute($url)
    {
        $host = $this->request->getHost();
        $scheme = $this->request->getScheme();
        
        if ($scheme && $host) $prefix = $scheme.'://'.$host;
        else $prefix = $this->absoluteaux;
        
        return $prefix.$url;
    }
    
    public function temphash($tempfile)
    {
        return sha1($tempfile.'xSkh78d');
    }
    
    public function getType($path)
    {
        $info = getimagesize($path);

        if (false === $info) {
            throw new \RuntimeException('Could not collect image metadata');
        }

        list($width, $height, $type) = $info;

        $format = $this->types[$type];
        
        return $format;
    }
    
    public function show($path)
    {
        if (!file_exists($path)) throw new FileNotFoundException($path);
        
        $imagine = new Imagine();
        $image = $imagine->open($path);
        $type = $this->getType($path);
        
        $image->show($type);
    }
}