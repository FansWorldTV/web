<?php
namespace Dodici\Fansworld\WebBundle\Controller;

use Dodici\Fansworld\WebBundle\Entity\Profile;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\FormError;
use Application\Sonata\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Team;
use Dodici\Fansworld\WebBundle\Entity\IdolCareer;
use Application\Sonata\UserBundle\Entity\Notification;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Gd\Imagine;

/**
 * Profile controller
 * @Route("/profile")
 */
class ProfileController extends SiteController
{
    /**
     *  Get params:
     *  - type: 'all' | 'idol' | 'team'
     *  - filterby: 'popular' | 'activity'
     *  - genre: (Int) genreId of parent(genre) | genreId of child(subgenre) | null
     *  - limit (Int) | null
     *  - offset (Int) | null
     *  @Route("/ajax/getProfiles", name="getprofilesP_ajaxget")
     */
    public function getProfiles()
    {
        $request = $this->getRequest();
        $type = $request->get('type');
        $filterby = $request->get('filterby');
        $genre = $request->get('genre');
        $limit = $request->get('limit');
        $offset = $request->get('offset');

        if (!$type) $type = 'all';
        if (!$filterby) $filterby = 'popular';

        $entities = $this->getRepository('Profile')->latestOrPopular($type, $filterby, $genre, $limit, $offset);

        $response = array();
        foreach ($entities as $entity) {
            $response[] = array(
                'id' => $entity['id'],
                'type' => $entity['type'],
                'title' => $entity['title'],
                'slug' => $entity['slug'],
                'genre' => $entity['genre'],
                'fancount' => $entity['fancount'],
                'photocount' => $entity['photocount'],
                'videocount' => $entity['videocount']
            );
        }

        return $this->jsonResponse($response);
    }

}
