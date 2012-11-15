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
 * API controller - Video
 * V1
 * @Route("/api_v1")
 */
class VideoController extends BaseController
{
	/**
     * [signed if user_id given] Video - list
     * 
     * @Route("/video/list", name="api_video_list")
     * @Method({"GET"})
     *
     * Get params:
     * - <optional> recommended: boolean (0|1), if true get only videos of possible interest to user
     * - <optional> user_id: int, required for recommended = 1, will also be used for privacy filtering
     * - <required if user_id given> user_token: string, user token
     * - <optional> highlight: boolean (0|1), if true get only highlighted videos, if false, get only non-highlighted videos
     * - <optional> category_id: filter by video category id
     * - <optional> extra_fields: comma-separated extra fields to return (see below)
     * - <optional> limit: int (amount of entities to return, default: LIMIT_DEFAULT)
     * - <optional> offset/page: int (amount of entities to skip/page number, default: none)
     * - <optional> sort: 'weight'|'createdAt' (default: weight)
     * - <optional> sort_order: 'asc'|'desc' (default: desc)
     * - [signature params if user_id given]
     * 
     * @return 
     * array (
     * 		array (
     * 			id: int,
     * 			title: string,
     * 			image: string (url of image),
     * 			highlight: boolean,
     * 			category_id: int,
     * 			
     * 			// extra fields
     * 			author: @see SecurityController::loginAction() - without token,
     * 			content: string,
     * 			createdAt: int (timestamp UTC),
     * 			duration: int (seconds),
     * 			visitCount: int,
     * 			likeCount: int,
     * 			commentCount: int
     * 			
     * 		),
     * 		...
     * 		)
     * 
     */
    public function listAction()
    {
        try {
            $request = $this->getRequest();
            
            // TODO: listado videos
            
            $recommended = $request->get('recommended');
            $userid = $request->get('user_id');
            $highlight = $request->get('highlight');
            $categoryid = $request->get('category_id');
            
            $user = null;
            if ($userid) {
                $user = $this->checkUserToken($userid, $request->get('user_token'));
            }
            
            $allowedfields = array('author', 'content', 'createdAt', 'duration', 'visitCount', 'likeCount', 'commentCount');
            
            $extrafieldsstr = $request->get('extra_fields');
            $extrafields = array();
            if ($extrafieldsstr) {
                $exp = explode(',', $extrafieldsstr);
                foreach ($exp as $x) {
                    if ($x && in_array($x, $allowedfields)) {
                        if (in_array($x, $extrafields)) throw new HttpException(400, 'Duplicate extra field: "'.$x.'"');
                        $extrafields[] = $x;
                    } else {
                        throw new HttpException(400, 'Invalid extra field: "'.$x.'"');
                    }
                }
            }
            
            $pagination = $this->pagination(array('weight', 'createdAt'), 'weight');
            $sortcriteria = $pagination['sort'];
            if ($sortcriteria == 'weight') $sortcriteria = 'default';
            if ($sortcriteria == 'createdAt') $sortcriteria = 'date';
            
            $videos = $this->getRepository('Video')->search(
                null,
                $user,
                $pagination['limit'],
                $pagination['offset'],
                $categoryid,
                $highlight,
                null,
                null,
                null,
                $sortcriteria,
                null,
                null,
                null,
                $recommended,
                $pagination['sort_order']
            );
            
            $return = array();
            
            foreach ($videos as $video) {
                $rv = array(
                    'id' => $video->getId(),
                    'title' => (string)$video,
                    'image' => $video->getImage() ? $this->get('appmedia')->getImageUrl($video->getImage()) : null,
                    'highlight' => $video->getHighlight(),
                    'category_id' => $video->getVideocategory()->getId()
                );
                
                foreach ($extrafields as $x) {
                    switch ($x) {
                        case 'author':
                            $rv['author'] = $video->getAuthor() ? $this->userArray($video->getAuthor()) : null;
                            break;
                        case 'createdAt':
                            $rv['createdAt'] = $video->getCreatedAt()->format('U');
                            break;
                        default:
                            $methodname = 'get'.ucfirst($x);
                            $rv[$x] = $video->$methodname();
                            break;
                    }
                }
                
                $return[] = $rv;
            }
            
            return $this->jsonResponse($return);
        } catch (\Exception $e) {
            return $this->plainException($e);
        }
    }
}
