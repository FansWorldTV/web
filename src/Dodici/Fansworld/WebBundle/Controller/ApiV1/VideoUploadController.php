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
     * [signed] Get upload url and token
     * 
     * @Route("/video/upload/token", name="api_v1_video_upload_token")
     * @Method({"GET"})
     *
     * Get params:
     * - [signature params]
     * 
     * @return 
     * array(
     *     url: string,
     *     params: array(
     *         uploadTokenId: string,
     *         service: 'uploadToken',
     *         action: 'upload'
     *         
     *         //you then have to add these:
     *         fileData: file,
     *         <optional> resume: boolean,
     *         <optional> resumeAt: float,
     *         <optional> finalChunk: boolean
     *     )
     * )
     */
    public function tokenAction()
    {
        try {
            if ($this->hasValidSignature()) {
                $kaltura = $this->get('kaltura');
                $url = $kaltura->getApiUrl();
                $service = 'uploadToken';
                $action = 'upload';
                $token = $kaltura->getUploadToken();
                
                return $this->result(
                    array(
                        'url' => $url,
                        'token' => $token,
                        'params' => array(
                            'uploadTokenId' => $token,
                            'service' => $service,
                            'action' => $action
                        )
                    )
                );
                
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }

	/**
     * [signed] Create entry
     * 
     * @Route("/video/upload/entry", name="api_v1_video_upload_entry")
     * @Method({"POST"})
     *
     * Post params:
     * - user_id: int
     * - [user token]
     * - upload_token: int
     * - video_title: string
     * - video_content: string
     * - video_category: int
     * - [signature params]
     * 
     * @return 
     * array (
     *     video_id: int
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
                
                $token = $request->get('upload_token');
                
                $title = $request->get('video_title');
                $content = $request->get('video_content');
                $vcid = $request->get('video_category');
                $vc = $this->getRepository('VideoCategory')->find($vcid);
                if (!$title) throw new HttpException(400, 'Requires video_title');
                if (!$content) throw new HttpException(400, 'Requires video_content');
                if (!$vcid) throw new HttpException(400, 'Requires video_category');
                if (!$vc) throw new HttpException(400, 'Invalid video_category');
                
                $entryid = $kaltura->addEntryFromToken($token, $title);
                
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
                
                return $this->result(array(
                    'video_id' => $video->getId()
                ));
                
            } else {
                throw new HttpException(401, 'Invalid signature');
            }
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
