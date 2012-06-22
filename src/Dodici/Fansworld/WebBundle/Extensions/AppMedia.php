<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\Request;

class AppMedia
{
    protected $request;
    protected $mediapool;
    protected $manager;

    function __construct($mediapool, $manager)
    {
        $this->mediapool = $mediapool;
        $this->manager = $manager;
        $this->request = Request::createFromGlobals();
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
}