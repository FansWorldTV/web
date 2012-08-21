<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;
use Dodici\Fansworld\WebBundle\Entity\Notification;
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
	protected $security_context;
    protected $user;
	protected $uploadpath;

    function __construct(EntityManager $em, RequestBuilder $api, FtpWriter $ftp, AppMedia $appmedia, SecurityContext $security_context, $uploadpath)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->api = $api;
        $this->ftp = $ftp;
        $this->appmedia = $appmedia;
        $this->security_context = $security_context;
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
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
        
        $extension = $file->getExtension();
    	$basename = $file->getBasename('.'.$extension);
    	
    	if ($filename) {
    	    $lastdot = strrpos($filename, '.');
            if (!$lastdot) throw new \Exception('Invalid filename');
            $nameext = substr($filename, $lastdot);
            $showname = substr($filename, 0, $lastdot);
    	} else {
    	    $nameext = $file->guessExtension() ?: $extension;
    	    $showname = $basename;
    	}
        
        $hash = uniqid();
        $newname = $basename .'_'. $hash . '.' . $extension;
        
        $file->move($this->uploadpath, $newname);
		
    	$token = $this->api->createMetadata($showname, $newname, 'video', 'uservideos|user_' . $author->getId(), new \DateTime(), array('published' => 1));
        
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
                        if (isset($pod->duration) && $pod->duration) {
                            $video->setDuration(intval($pod->duration));
                        }
                        $this->em->persist($video);
                        
                        // notify the user his video finished processing
                        $notification = new Notification();
        	    		$notification->setType(Notification::TYPE_VIDEO_PROCESSED);
        	    		$notification->setAuthor($video->getAuthor());
        	    		$notification->setTarget($video->getAuthor());
        	    		$notification->setVideo($video);
        	    		$this->em->persist($notification);
                        
                        $this->em->flush();
                    }
                } catch (\Exception $e) {
                    // might be 404, thumb not ready yet
                }
            }
        }
    }
    
    public function createVideoFromUrl($url, $user=null)
    {
        if (!$user) $user = $this->user;
        if (!($user instanceof User)) throw new AccessDeniedException('Tried to create video with no user logged in');
        
        $idyoutube = $this->getYoutubeId($url);
        $idvimeo = $this->getVimeoId($url);
        $duration = null;
        
        if ($idyoutube) {
            $metadata = $this->getYoutubeMetadata($idyoutube);
            
            $thumbs = $metadata['media$group']['media$thumbnail'];
            $thumburl = $thumbs[0]['url'];
            
            $image = null;
            if ($thumburl) {
                $image = $this->appmedia->createImageFromUrl($thumburl);
            }
            $title = $metadata['title']['$t'];
            $description = $metadata['content']['$t'];
            $duration = $metadata['media$group']['yt$duration']['seconds'];
        } 
        
        if ($idvimeo) {
            $metadata = $this->getVimeoMetadata($idvimeo);
            
            $image = null;
            if ($metadata['thumbnail_large']) {
                $image = $this->appmedia->createImageFromUrl($metadata['thumbnail_large']);
            }
            $title = $metadata['title'];
            $description = $metadata['description'];
            $duration = $metadata['duration'];
        } 

        if ($idvimeo || $idyoutube) {
            $video = new Video();
            $video->setAuthor($user);
            $video->setTitle($title);
            $video->setContent($description);
            $video->setYoutube($idyoutube);
            $video->setVimeo($idvimeo);
            $video->setImage($image);
            $video->setDuration($duration);
            
            return $video;
        } else {
            throw new \Exception('Could not parse URL');
        }
    }
    
	/**
     * @throws \RuntimeException
     * @param string $id
     * @return mixed|null|string
     */
    private function getYoutubeMetadata($id)
    {
        if (!$id) {
            return null;
        }

        $url = sprintf('https://gdata.youtube.com/feeds/api/videos/%s?alt=json', $id);
        $metadata = @file_get_contents($url);

        if (!$metadata) {
            throw new \RuntimeException('Unable to retrieve youtube video information for :' . $url);
        }

        $metadata = json_decode($metadata, true);

        if (!$metadata || !isset($metadata['entry'])) {
            throw new \RuntimeException('Unable to decode youtube video information for :' . $url);
        }

        return $metadata['entry'];
    }
    
	/**
     * @throws \RuntimeException
     * @param string $id
     * @return mixed|null|string
     */
    private function getVimeoMetadata($id)
    {
        if (!$id) {
            return null;
        }

        $url = sprintf('http://vimeo.com/api/v2/video/%s.json', $id);
        $metadata = @file_get_contents($url);

        if (!$metadata) {
            throw new \RuntimeException('Unable to retrieve vimeo video information for :' . $url);
        }

        $metadata = json_decode($metadata, true);

        if (!$metadata) {
            throw new \RuntimeException('Unable to decode vimeo video information for :' . $url);
        }

        return $metadata[0];
    }
    
    private function getYoutubeId($youtube)
    {
    	if ((strpos($youtube, 'youtube.com') !== false) || (strpos($youtube, 'youtu.be') !== false)) {
            $youtube = str_replace(
    		array('http://','www.','youtube.com/watch?v=','youtu.be/','youtube.com/v/'), 
    		array('','','','',''), 
    		$youtube);
    		if (strpos($youtube, '&') !== false) {
    			$youtube = substr($youtube, 0, strpos($youtube, '&'));
    		}
    		return $youtube;
		} else {
		    return null;
		}
    }
    
    private function getVimeoId($vimeo)
    {
        if (strpos($vimeo, 'vimeo.com') !== false) {
            $vimeo = str_replace(
    		array('http://','https://','www.','vimeo.com/'), 
    		array('','','',''), 
    		$vimeo);
    		if (strpos($vimeo, '?') !== false) {
    			$vimeo = substr($vimeo, 0, strpos($vimeo, '?'));
    		}
    		return $vimeo;
        } else {
            return null;
        }
    }
    
    public function createVideoFromBinary($videocontent, User $author, $filename=null)
    {
        if ($videocontent) {
            $tmpfile = tempnam('/tmp', 'IYT');
            file_put_contents($tmpfile, $videocontent);
            return $this->upload($tmpfile, $author,$filename);
        } else {
            throw new \Exception('No binary video content');
        }
    }
}