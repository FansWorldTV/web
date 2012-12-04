<?php
namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;



/**
 * Media controller.
 * @Route("/media")
 */
class MediaController extends SiteController
{
    /**
     * @Route("/temp", name= "media_temp")
     */
    public function showAction()
    {
        $request = $this->getRequest();
        $tempfile = $request->get('tempfile');
        $hash = $request->get('hash');
        
        if (!$tempfile || !$hash) throw new HttpException(400, 'Invalid parameters');
        if ($hash != $this->get('appmedia')->temphash($tempfile)) throw new HttpException(400, 'Invalid hash');
        
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempfile;
        
        $this->get('appmedia')->show($file);
        exit;
    }

}