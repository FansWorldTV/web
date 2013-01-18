<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\ApiV1\BaseController;

/**
 * API controller - Image
 * V1
 * @Route("/api_v1")
 */
class ImageController extends BaseController
{
	/**
     * [signed] Get image url by format
     * 
     * @Route("/image/{id}", name="api_v1_image_url", requirements = {"id" = "\d+"})
     * @Method({"GET"})
     *
     * Get params:
     * - imageformat: string
     * - [signature params]
     * 
     * @return 
     * string (url)
     */
    public function imageUrlAction($id)
    {
        try {
            if ($this->hasValidSignature()) {
                $image = $this->getDoctrine()->getRepository('ApplicationSonataMediaBundle:Media')->find($id);
                if (!$image) throw new HttpException(404, 'Media not found');
                $format = $this->getImageFormat();
                
                return $this->result($this->getImageUrl($image, $format));
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
    
	/**
     * Get available formats
     * @Route("/image/formats", name="api_v1_image_formats")
     * @Method({"GET"})
     * 
     * @return
     * array (
     *		array (
     * 			name: string,
     * 			width: int,
     * 			height: int,
     * 			quality: int,
     * 			constraint: boolean
     * 		)
     * )
     */
    public function formatsAction()
    {
        try {
            $formats = $this->get('sonata.media.pool')->getFormatNamesByContext('default');
            $return = array();
            
            foreach ($formats as $name => $format) {
                $return[] = array(
                    'name' => str_replace('default_', '', $name),
                    'width' => $format['width'],
                    'height' => $format['height'],
                    'quality' => $format['quality'],
                	'constraint' => $format['constraint']
                );
            }
            
            return $this->result($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
