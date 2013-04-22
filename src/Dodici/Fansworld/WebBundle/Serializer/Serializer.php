<?php

namespace Dodici\Fansworld\WebBundle\Serializer;
use Doctrine\Common\Collections\Collection;


/**
 * Serializer base class
 *
 * To add extra fields for a class, create a "serializer.xxx" service (e.g. serializer.video in Serializer\Video)
 * with a public "values" method that returns the key/value pairs for merging
 */
class Serializer
{
    protected $container;

    function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Return json-encoded serialization of entity
     * @param mixed $entity
     * @param string $imageformat
     */
    public function json($entity, $imageformat = 'small')
    {
        return json_encode($this->values($entity, $imageformat));
    }

    /**
     * Return key/value array for an entity, or array of entities
     * @param mixed $entity
     * @param string $imageformat
     */
    public function values($entity, $imageformat = 'small', $splashformat = 'big')
    {
        if (!$entity && !is_array($entity)) return null;
        if ($entity instanceof Collection) $entity = $entity->toArray();

        if (is_object($entity)) {
            $props = array();

            if (method_exists($entity, 'getId')) $props['id'] = $entity->getId();
            if (method_exists($entity, 'getSlug')) $props['slug'] = $entity->getSlug();
            if (method_exists($entity, '__toString')) {
                $props['title'] = (string)$entity;
            } elseif (method_exists($entity, 'getTitle')) {
                $props['title'] = $entity->getTitle();
            }

            if (method_exists($entity, 'getImage')) {
                $appmedia = $this->container->get('appmedia');
                $image = $entity->getImage();
                $imageurl = $image ? $appmedia->getImageUrl($entity->getImage(), $imageformat) : null;
                $props['image'] = $imageurl;
            }

            if (method_exists($entity, 'getCreatedAt')) {
                $props['createdAt'] = $entity->getCreatedAt() ? $entity->getCreatedAt()->format('U') : null;
            }

            $type = $this->getType($entity);
            if ($this->container->has('serializer.'.$type)) {
                $entserializer = $this->container->get('serializer.'.$type);
                $extrafields = $entserializer->values($entity, $imageformat, $splashformat);

                $props = array_merge($props, $extrafields);
            }

            return $props;
        } elseif (is_array($entity)) {
            $arr = array();
            foreach ($entity as $k => $e) {
                $arr[$k] = $this->values($e, $imageformat, $splashformat);
            }
            return $arr;
        } else {
            return $entity;
        }

        return null;
    }

    private function getType($entity)
    {
        $name = $this->container->get('doctrine.orm.entity_manager')->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }
}