<?php
namespace DataFactory\FeedBundle\Feeder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\DoctrineBundle\Registry;
use DataFactory\FeedBundle\Entity\XmlData;

class Feeder {
    /** @var \Symfony\Bundle\DoctrineBundle\Registry */
    private $orm;
    private $xmlrequest;

    public function __construct($orm, $xmlrequest)
    {
        $this->orm = $orm;
        $this->xmlrequest = $xmlrequest;
    }
    
    public function refresh($type)
    {
        $params = array();
        $em = $this->orm->getEntityManager();
        
        $xrepo = $em->getRepository('DataFactory\FeedBundle\Entity\XmlData');
        $lastchangeddate = $xrepo->lastChangedDate($type);
        
        if ($lastchangeddate) {
            $params['desde'] = $lastchangeddate->format('Ymd');
            $params['hora'] = $lastchangeddate->format('H:i:s');
        }
        
        $xmlchannels = $this->xmlrequest->request($params);
        $xmls = array();
        
        foreach ($xmlchannels->canal as $canal) {
            $channelname = (string)$canal;
            $exp = explode('.', $channelname);
            if (end($exp) == $type) {
                $xmldata = $xrepo->findOneByChannel($type);
                $channelxml = $this->xmlrequest->request( array( 'canal' => $channelname ) );
                if ($channelxml) {
                    if ($xmldata) {
                        $xmldata->setChanged(new \DateTime());
                    } else {
                        $xmldata = new XmlData();
                        $xmldata->setChannel($channelname);
                    }
                    
                    $xmldata->setData($channelxml->asXML());
                    
                    $xmls[] = $channelxml;
                    $em->persist($xmldata);
                }
            }
        }
        
        $em->flush();
        
        return $xmls;
    }
    
    public function getPending($type)
    {
        $em = $this->orm->getEntityManager();
        $xrepo = $em->getRepository('DataFactory\FeedBundle\Entity\XmlData');
        $pending = $xrepo->pending($type);
        
        return $pending;
    }
}