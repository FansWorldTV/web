<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Share;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Request;
use Dodici\Fansworld\WebBundle\Entity\Video;

/**
 * Comment controller.
 * @Route("/comment")
 */
class CommentController extends SiteController
{

    /**
     * @Route("/show/{id}", name= "comment_show", requirements = {"id" = "\d+"})
     * @Template
     */
    public function showAction($id)
    {
        // TODO: comment show action, list all responses (nested comments), allow answering root comment
        $comment = $this->getRepository('Comment')->find($id);
        $this->securityCheck($comment);
        if ($comment->getComment())
            throw new HttpException(400, 'Comentario es subcomentario');

        return array(
            'comment' => $comment
        );
    }

    /**
     * 
     * @Route("/ajax/post", name="comment_ajaxpost")
     */
    public function ajaxPostAction()
    {
        try {
            $request = $this->getRequest();
            $id = intval($request->get('id'));
            $type = $request->get('type');
            $privacy = $request->get('privacy', null);
            $content = $request->get('content', null);
            $ispin = $request->get('ispin') == 'true';
            $translator = $this->get('translator');
            $appstate = $this->get('appstate');
            $videoShare = false;

            if (!in_array($type, array('newspost', 'photo', 'video', 'album', 'contest', 'comment', 'user', 'team', 'idol')))
                throw new \Exception('Invalid type');

            if (!$content)
                throw new \Exception('You must enter a message');

            $repo = $this->getRepository(ucfirst($type));
            $entity = $repo->find($id);

            if (!$entity)
                throw new \Exception('Entity does not exist');
            if (!$appstate->canComment($entity))
                throw new \Exception('Unauthorized');

            $message = null;
            $user = $this->getUser();
            $em = $this->getDoctrine()->getEntityManager();

            $isYoutubeUrl = $this->_isYoutubeUrl($content);
            if ($isYoutubeUrl) {
                $this->_createAndShareVideo($content);
                $jsonComment = "";
                $videoShare = true;
                $message = "Video compartido con exito";
            } else {
                $comment = $this->get('commenter')->comment($user, $entity, $content, $privacy);
                if ($entity instanceof Comment) {
                    $message = $translator->trans('You have replied to a comment');
                } else {
                    $message = $translator->trans('You have commented on') . ' ' . (string) $entity;
                }
                $templatename = ($ispin ? 'pin_comment.html.twig' : 'comment.html.twig');
                $jsonComment = $this->jsonComment($comment, 'true');
            }

            return $this->jsonResponse(array(
                        'jsonComment' => $jsonComment,
                        'message' => $message,
                        'videoShare' => $videoShare,
                        'authorAvatarUrl' => $this->getImageUrl($user->getImage(), 'small'),
                        'authorProfileUrl' => $this->generateUrl('user_land', array('username' => $user->getUsername()))
                    ));
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

    /**
     *  @Route("/ajax/get", name="comment_ajaxget")
     */
    public function ajaxGetAction()
    {
        $request = $this->getRequest();

        $commentId = $request->get('id', false);
        $wallname = $request->get('wall');
        $limit = $request->get('limit');
        $lastid = $request->get('lastid');

        $useJson = $request->get('usejson');


        $response = array();

        if ($commentId) {
            $comment = $this->getRepository('Comment')->find($commentId);
            if ($this->get('appstate')->canView($comment)) {
                $response = $this->jsonComment($comment);
            } else {
                throw new HttpException(401, 'Cannot view comment, unauthorized');
            }
        } elseif ($wallname && !$commentId) {
            $exp = explode('_', $wallname);
            if (count($exp) != 2)
                throw new HttpException(400, 'Invalid wall id');

            $type = $exp[0];
            $entityId = intval($exp[1]);

            $allowedtypes = array('user', 'video', 'photo', 'album', 'contest', 'proposal', 'team', 'event', 'meeting', 'idol');

            if (!in_array($type, $allowedtypes) || !$entityId)
                throw new HttpException(400, 'Invalid wall id');

            $entity = $this->getRepository($type)->find($entityId);

            if (!$entity || ($entity && method_exists($entity, 'getActive') && !$entity->getActive()))
                throw new HttpException(400, 'Wall entity not found');

            $comments = $this->get('appstate')->getComments($entity, $lastid, $limit);
            foreach ($comments as $comment) {
                $response[] = $this->jsonComment($comment, $useJson);
            }
        } else {
            throw new HttpException(400, 'Invalid request parameters');
        }

        return $this->jsonResponse($response);
    }

    /**
     *  @Route("/ajax/get/subcomments", name="subcomment_ajaxget")
     */
    public function ajaxGetSubcommentsAction()
    {
        $request = $this->getRequest();
        $commentIds = $request->get('wallCommentIds', false);
        $repo = $this->getRepository('Comment');
        $response = array();

        if (empty($commentIds)) {
            return $this->jsonResponse($response);
        }

        if (!is_array($commentIds)) {
            $commentIds = array($commentIds);
        }

        foreach ($commentIds as $commentId) {
            $entity = $repo->find($commentId);
            $subcomments = $repo->getCommentSubcomments($entity);
            if (!empty($subcomments)) {
                foreach ($subcomments as $subcomment) {
                    $response[(string) $commentId][] = array(
                        'content' => $subcomment->getContent(),
                        'authorAvatarUrl' => $this->getImageUrl($subcomment->getAuthor()->getImage(), 'small_square')
                    );
                }
            }
        }

        return $this->jsonResponse($response);
    }

    private function jsonComment(Comment $comment, $useJson = true)
    {
        $appMedia = $this->get('appmedia');
        if ($useJson != true) {
            return $this->renderView('DodiciFansworldWebBundle:Comment:comment.html.twig', array('comment' => $comment));
        }

        $appstate = $this->get('appstate');

        $author = array(
            'authorId' => $comment->getAuthor()->getId(),
            'authorName' => (string) $comment->getAuthor(),
            'authorAvatarUrl' => $appMedia->getImageUrl($comment->getAuthor()->getImage(), 'headeravatar'),
            'authorWallUrl' => $this->generateUrl('user_land', array('username' => $comment->getAuthor()->getUsername())),
        );

        $type = array(
            'typeId' => $comment->getType(),
            'typeName' => $comment->getTypeName(),
            'type' => $this->get('appstate')->getType($comment),
        );

        $tag = $this->getTagItem($comment);

        $target = $this->getTarget($comment);

        $commentArray = array(
            'id' => $comment->getId(),
            'canDelete' => $appstate->canDelete($comment),
            'entityType' => 'comment',
            'content' => $comment->getContent(),
            'time' => $comment->getCreatedAt()->format('c'),
            'commentCount' => $comment->getCommentCount(),
            'templateId' => 'comment-' . $comment->getTypeName(),
        );

        $commentArray = array_merge($commentArray, $tag, $type, $author, $target);

        return $commentArray;
    }

    private function getTarget(Comment $comment)
    {
        $target = $comment->getTarget();
        $targetData = array();
        if (is_null($target)) {
            return array();
        } else {
            $targetData = array(
                'targetId' => $target->getId(),
                'targetName' => (string) $target,
                'targetAvatarUrl' => $this->get('appmedia')->getImageUrl($target->getImage(), 'headeravatar'),
                'targetWallUrl' => $this->generateUrl('user_land', array('username' => $target->getUsername())),
            );
        }

        return $targetData;
    }

    private function getTagItem(Comment $comment)
    {
        $appMedia = $this->get('appmedia');
        $tag = array();
        $share = $comment->getShare();

        /* if (is_null($share)) {
          if ($this->get('appstate')->getType($comment) == 'comment') {
          $type = 'comment';
          $tag['shareUrl'] = $this->generateUrl($type . '_show', array(
          'id' => $comment->getId()
          ));
          $tag['shareType'] = $type;
          $tag['shareId'] = $comment->getId();
          $tag['shareTitle'] = $comment->__toString();
          $tag['shareActiontext'] = $this->trans('shared_' . $type);
          $tag['shareLikeCount'] = $comment->getLikeCount();
          $tag['shareCommentCount'] = $comment->getCommentCount();
          if ($this->get('appstate')->canDislike($comment)) {
          $tag['shareLikeClass'] = 'liked';
          }else
          $tag['shareLikeClass'] = '';
          }

          return $tag;
          } */

        if (!$share) return array();

        $validTypes = Share::getTypes();

        foreach ($validTypes as $type) {
            $getType = 'get' . ucfirst($type);
            if (!is_null($share->$getType())) {
                $tag_item = $share->$getType();
                if ($type == 'comment') {
                    $tag['shareUrl'] = $this->generateUrl($type . '_show', array(
                        'id' => $tag_item->getId()
                            ));
                } else {
                    $tag['shareUrl'] = $this->generateUrl($type . '_show', array(
                        'id' => $tag_item->getId(),
                        'slug' => $tag_item->getSlug()
                            ));
                    if (method_exists($tag_item, 'getImage') && $tag_item->getImage()) {
                        $tag['shareImage'] = $appMedia->getImageUrl($tag_item->getImage(), 'wall');
                    }
                }
                $tag['shareType'] = $type;
                $tag['shareId'] = $tag_item->getId();
                $tag['shareTitle'] = $tag_item->__toString();
                $tag['shareActiontext'] = $this->trans('shared_' . $type);
                $tag['shareLikeCount'] = method_exists($tag_item, 'getLikeCount') ? $tag_item->getLikeCount() : null;
                $tag['shareCommentCount'] = $comment->getCommentCount();
                if ($this->get('appstate')->canDislike($tag_item)) {
                    $tag['shareLikeClass'] = 'liked';
                }else
                    $tag['shareLikeClass'] = '';

                break;
            }
        }


        return $tag;
    }

    private function _createAndShareVideo($content) {
        $video = $this->get('video.uploader')->createVideoFromUrl($content, $this->getUser());
        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($video);
        $em->flush();
        $this->get('sharer')->share($video, $this->getUser(), $video->getTitle(), $this->getUser());
    }

    private function _isYoutubeUrl($youtube)
    {
        if ((strpos($youtube, 'youtube.com') !== false) || (strpos($youtube, 'youtu.be') !== false)) {
            $youtube = str_replace(
            array('http://','www.','youtube.com/watch?v=','youtu.be/','youtube.com/v/'), 
            array('','','','',''), 
            $youtube);
            if (strpos($youtube, '&') !== false) {
                $youtube = substr($youtube, 0, strpos($youtube, '&'));
            }
            return trim($youtube);
        } else {
            return null;
        }
    }
}
