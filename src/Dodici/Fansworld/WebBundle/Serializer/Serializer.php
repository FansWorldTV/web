<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Serializer base class
 */
class Serializer
{
    protected $container;
    
    function __construct($container)
    {
        $this->container = $container;
    }
    
    public function json($entity, $imageformat = 'small')
    {
        return json_encode($this->values($entity, $imageformat));
    }
    
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
            foreach ($entity as $e) {
                $arr[] = $this->values($e);
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