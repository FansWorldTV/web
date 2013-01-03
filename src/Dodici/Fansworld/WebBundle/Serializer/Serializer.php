<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

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
    public function values($entity, $imageformat = 'small')
    {
        if (!$entity) return null;
        
        if (is_object($entity)) {
            $props = array();
            
            if (property_exists($entity, 'id')) $props['id'] = $entity->getId();
            if (property_exists($entity, 'slug')) $props['slug'] = $entity->getSlug();
            if (method_exists($entity, '__toString')) {
                $props['title'] = (string)$entity;
            } elseif (property_exists($entity, 'title')) {
                $props['title'] = $entity->getTitle();
            }
            
            if (property_exists($entity, 'image')) {
                $appmedia = $this->container->get('appmedia');
                $image = $entity->getImage();
                $imageurl = $image ? $appmedia->getImageUrl($entity->getImage(), $imageformat) : null;
                $props['image'] = $imageurl;
            }
            
            if (property_exists($entity, 'createdAt')) {
                $props['createdAt'] = $entity->getCreatedAt() ? $entity->getCreatedAt()->format('U') : null;
            }
            
            $type = $this->getType($entity);
            if ($this->container->has('serializer.'.$type)) {
                $entserializer = $this->container->get('serializer.'.$type);
                $extrafields = $entserializer->values($entity);
                
                $props = array_merge($props, $extrafields);
            }
            
            return $props;
        } elseif (is_array($entity)) {
            $arr = array();
            foreach ($entity as $k => $e) {
                $arr[$k] = $this->values($e, $imageformat);
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