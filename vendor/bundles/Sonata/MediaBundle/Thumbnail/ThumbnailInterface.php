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

interface ThumbnailInterface
{
    /**
     * @abstract
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param $format
     */
    function generatePublicUrl(MediaProviderInterface $provider, MediaInterface $media, $format);

    /**
     * @abstract
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param $format
     */
    function generatePrivateUrl(MediaProviderInterface $provider, MediaInterface $media, $format);

    /**
     * @abstract
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     */
    function generate(MediaProviderInterface $provider, MediaInterface $media);

    /**
     * @abstract
     * @param \Sonata\MediaBundle\Provider\MediaProviderInterface $provider
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     */
    function delete(MediaProviderInterface $provider, MediaInterface $media);
}