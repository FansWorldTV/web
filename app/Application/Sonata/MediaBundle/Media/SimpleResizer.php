<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Sonata\MediaBundle\Media;

use Imagine\Image\ImagineInterface;
use Imagine\Image\Box;
use Gaufrette\File;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Media\SimpleResizer as BaseResizer;

class SimpleResizer extends BaseResizer
{
    /**
     * @param \Sonata\MediaBundle\Model\MediaInterface $media
     * @param \Gaufrette\File $in
     * @param \Gaufrette\File $out
     * @param string $format
     * @param array $settings
     * @return void
     */
    public function resize(MediaInterface $media, File $in, File $out, $format, $settings)
    {
        if (!isset($settings['width'])) {
            throw new \RuntimeException(sprintf('Width parameter is missing in context "%s" for provider "%s"', $media->getContext(), $media->getProviderName()));
        }

        $image = $this->getAdapter()->load($in->getContent());
        $size = $image->getSize();
        
        if ($settings['height'] == null) {
            $settings['height'] = (int) ($settings['width'] * $size->getHeight() / $size->getWidth());
        }
        
        if ($settings['constraint'] == false) {
        	if ($size->getWidth() < $settings['width']) {
        		$settings['width'] = $size->getWidth();
        		$settings['height'] = $size->getHeight();
        	}
        }

        $content = $image
            ->thumbnail(new Box($settings['width'], $settings['height']), $this->getMode())
            ->get($format);

        $out->setContent($content);
    }
}