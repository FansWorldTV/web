<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Tag;

/**
 * Interest controller.
 * @Route("/tag")
 */
class TagController extends SiteController
{

    /**
     *  get params (all optional):
     *   - text (partial match)
     *   - page
     *  @Route("/ajax/get", name="tag_ajaxget")
     */
    public function ajaxTags()
    {
        $request = $this->getRequest();
        $text = $request->get('text');
        $page = $request->get('page');
        $limit = null;
        $offset = null;

        if ($page !== null) {
            $page--;
            $limit = self::LIMIT_AJAX_GET;
            $offset = $limit * $page;
        }

        $tags = $this->getRepository('Tag')->matching($text, $limit, $offset);

        $response = array();
        foreach ($tags as $tag) {
            $response[] = array(
                'id' => $tag->getId(),
                'value' => $tag->getTitle(),
                'add' => $tag->getTitle(),
            );
        }

        return $this->jsonResponse($response);
    }

    /**
     *  get params (all optional):
     *   - text (partial match)
     *   - page
     *  @Route("/ajax/get-used-in-videos", name="tag_ajaxgetusedinvideos")
     */
    public function ajaxTagsUsedInVideos()
    {
        $tagger = $this->get('tagger');
        $request = $this->getRequest();
        $page = $request->get('page');
        $limit = null;
        $offset = null;

        if ($page !== null) {
            $page--;
            $limit = 20; //TODO: Juan
            $offset = $limit * $page;
        }

        $filterType = $request->get('filter', 'popular');
        $videoCategory = $request->get('channel');
        $genre = $request->get('genre');

        $response = array(
            'tags' => false,
            'error' => false
        );

        try {
            if($filterType == 'followed'){
                $user = $this->getUser();
                $response['tags'] = $tagger->trendingInRecommended($user, $limit, $offset);
            }else{
                $response['tags'] = $tagger->usedInVideos($filterType, $videoCategory, $genre, $limit, $offset);
            }
        } catch (Exception $exc) {
            $response['error'] = $exc->getMessage();
        }

        return $this->jsonResponse($response);
    }

    /**
     *  get params:
     *   - text
     *   - limit
     *  @Route("/ajax/matchall", name="tag_ajaxmatch")
     */
    public function matchAll()
    {
        $request = $this->getRequest();
        $text = $request->get('text');
        $limit = $request->get('limit', 4);

        $tags = $this->getRepository('Tag')->matchAll($text, $this->getUser(), $limit);

        $response = array();

        $c = 0;
        foreach ($tags as $type => $ents) {
            foreach ($ents as $ent) {
                $r = array(
                    'id' => $c,
                    'label' => (string) $ent,
                    'value' => (string) $ent,
                );

                $entjson = array(
                    'id' => $ent->getId(),
                    'type' => $type
                );
                if (property_exists($ent, 'slug'))
                    $entjson['slug'] = $ent->getSlug();
                if (property_exists($ent, 'username'))
                    $entjson['username'] = $ent->getUsername();
                if (property_exists($ent, 'image'))
                    $entjson['image'] = $this->getImageUrl($ent->getImage(), 'micro_square');
                $r['result'] = $entjson;
                $response[] = $r;
                $c++;
            }
        }

        return $this->jsonResponse($response);
    }

}
