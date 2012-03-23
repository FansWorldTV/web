<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Sonata\MediaBundle\Thumbnail;

use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\MediaProviderInterface;
use Sonata\MediaBundle\Thumbnail\FormatThumbnail as BaseFormatThumbnail;

class FormatThumbnail extends BaseFormatThumbnail
{
    /**
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param $format
     * @return mixed
     */
    public function generatePublicUrl(MediaProviderInterface $provider, MediaInterface $media, $format)
    {
        if ($format == 'reference') {
            $path = $provider->getReferenceImage($media);
        } else {
            $path = sprintf('%s/thumb_%s_%s_%s.jpg',  
            	$provider->generatePath($media), 
            	$media->getId(), 
            	$format, 
            	substr($media->getProviderReference(), 0, strrpos($media->getProviderReference(), '.'))
            	);
        }

        return $provider->getCdnPath($path, $media->getCdnIsFlushable());
    }

    /**
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param $format
     * @return string
     */
    public function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, $format)
    {
        return sprintf('%s/thumb_%s_%s_%s.jpg',
            $provider->generatePath($media),
            $media->getId(),
            $format,
            substr($media->getProviderReference(), 0, strrpos($media->getProviderReference(), '.'))
        );
    }

}