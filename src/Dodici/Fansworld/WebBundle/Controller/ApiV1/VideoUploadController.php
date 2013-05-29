<?php

namespace Dodici\Fansworld\WebBundle\Controller\ApiV1;

use Dodici\Fansworld\WebBundle\Entity\Video;
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
 * API controller - Video Uploads
 * V1
 * @Route("/api_v1")
 */
class VideoUploadController extends BaseController
{

	/**
     * [signed] Create entry
     * After uploading to the token, user will have to wait until the video is processed via batch
     * He will receive a TYPE_VIDEO_PROCESSED notification when it is ready
     * 
     * @Route("/video/upload/entry", name="api_v1_video_upload_entry")
     * @Method({"POST"})
     *
     * Post params:
     * - user_id: int
     * - [user token]
     * - video_title: string
     * - video_content: string
     * - video_category: int
     * - [signature params]
     * 
     * @return 
     * array (
     *     video_id: int,
     *     url: string,
     *     token: string,
     *     params: array(
     *         uploadTokenId: string,
     *         service: 'uploadToken',
     *         action: 'upload',
     *         ks: string
     *         
     *         //you then have to add these:
     *         fileData: file,
     *         <optional> resume: boolean,
     *         <optional> resumeAt: float,
     *         <optional> finalChunk: boolean
     *     )
     * )
     */
    public function createEntryAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $request = $this->getRequest();
                $kaltura = $this->get('kaltura');
                
                $userid = $request->get('user_id');
                $user = $this->checkUserToken($userid, $request->get('user_token'));
                
                $title = $request->get('video_title');
                $content = $request->get('video_content');
                $vcid = $request->get('video_category');
                $vc = $this->getRepository('VideoCategory')->find($vcid);
                if (!$title) throw new HttpException(400, 'Requires video_title');
                if (!$content) throw new HttpException(400, 'Requires video_content');
                if (!$vcid) throw new HttpException(400, 'Requires video_category');
                if (!$vc) throw new HttpException(400, 'Invalid video_category');
                
                $uploadtoken = $kaltura->getUploadToken();
                $entryid = $kaltura->addEntryFromToken($uploadtoken, $title);
                
                if (!$entryid) throw new HttpException(500, 'Entry could not be created');
                
                $video = new Video();
                $video->setAuthor($user);
                $video->setTitle($title);
                $video->setContent($content);
                $video->setVideocategory($vc);
                $video->setActive(false);
                $video->setStream($entryid);
                
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($video);
                $em->flush();
                
                $url = $kaltura->getApiUrl();
                $service = 'uploadToken';
                $action = 'upload';
                
                return $this->result(array(
                    'video_id' => $video->getId(),
                    'url' => $url,
                    'token' => $uploadtoken,
                    'params' => array(
                        'uploadTokenId' => $uploadtoken,
                        'service' => $service,
                        'action' => $action,
                        'ks' => $kaltura->getKs()
                    )
                ));
                
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
