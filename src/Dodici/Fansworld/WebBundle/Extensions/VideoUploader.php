<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\File;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Flumotion\APIBundle\Builder\RequestBuilder;
use Flumotion\APIBundle\Ftp\FtpWriter;

class VideoUploader
{
	protected $request;
	protected $em;
	protected $api;
	protected $ftp;
	protected $appmedia;
	protected $uploadpath;

    function __construct(EntityManager $em, RequestBuilder $api, FtpWriter $ftp, AppMedia $appmedia, $uploadpath)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->api = $api;
        $this->ftp = $ftp;
        $this->appmedia = $appmedia;
        $this->uploadpath = $uploadpath;
    }

    /**
     * Prepares a video file for upload
     * 
     * @param string $filepath - path to the real file
     * 
     * @param User $author - user uploading the video
     * 
     * @param string|null $filename - optional filename
     * 
     */
    public function upload($filepath, User $author, $filename=null)
    {
        $file = new File($filepath);
        
        if ($filename) {
            $lastdot = strrpos($filename, '.');
            if (!$lastdot) throw new \Exception('Invalid filename');
            $extension = substr($filename, $lastdot);
            $basename = substr($filename, 0, $lastdot);
        } else {
            $filename = $file->getFilename();
    		$extension = $file->guessExtension() ?: $file->getExtension();
    		$basename = $file->getBasename('.'.$extension);
        }
        
        $hash = uniqid();
        $newname = $basename .'_'. $hash . '.' . $extension;
        
        $file->move($this->uploadpath, $newname);
		
    	$token = $this->api->createMetadata($basename, $newname, 'video', 'uservideos|user_' . $author->getId(), new \DateTime());
        
    	if ($token) {
    	    return intval($token);
    	} else {
    	    throw new \Exception('Metadata creation failed, no token obtained');
    	}
    }
    
    public function process(Video $video)
    {
        if (!$video->getStream()) throw new \Exception('Video has no Stream ID');
        if ($video->getProcessed()) throw new \Exception('Video has already been processed');
        
        $pod = $this->api->getPod($video->getStream());
        
        if ($pod) {
            $thumburl = $pod->video_image_url;
            
            if ($thumburl) {
                try {
                    $image = $this->appmedia->createImageFromUrl($thumburl);
                    if ($image) {
                        $video->setImage($image);
                        $video->setProcessed(true);
                        $video->setActive(true);
                        $this->em->persist($video);
                        
                        // TODO: SEND A NOTIFICATION TO THE USER
                        
                        $this->em->flush();
                    }
                } catch (\Exception $e) {
                    // might be 404, thumb not ready yet
                }
            }
        }
    }
}