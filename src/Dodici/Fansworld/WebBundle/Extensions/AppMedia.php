<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;

class AppMedia
{
    protected $request;
    protected $mediapool;

    function __construct($mediapool)
    {
        $this->mediapool = $mediapool;
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
}