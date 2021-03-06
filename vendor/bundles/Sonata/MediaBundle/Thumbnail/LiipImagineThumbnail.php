<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Thumbnail;

use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\MediaProviderInterface;
use Symfony\Component\Routing\RouterInterface;

class LiipImagineThumbnail implements ThumbnailInterface
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }


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
            $path = $this->router->generate(
                sprintf('_imagine_%s', $format),
                array('path' => sprintf('%s/%s_%s.jpg', $provider->generatePath($media), $media->getId(), $format))
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
        if ($format != 'reference') {
            throw new \RuntimeException('No private url for LiipImagineThumbnail');
        }

        $path = $provider->getReferenceImage($media);

        return $path;
    }

    /**
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @return mixed
     */
    public function generate(MediaProviderInterface $provider, MediaInterface $media)
    {
        // nothing to generate, as generated on demand
        return;
    }

    /**
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     */
    public function delete(MediaProviderInterface $provider, MediaInterface $media)
    {
        // feature not available
        return;
    }
}
