<?php
namespace Dodici\Fansworld\WebBundle\Feeder;

use Dodici\Fansworld\WebBundle\Entity\EventIncident;
use Dodici\Fansworld\WebBundle\Entity\HasTeam;
use Dodici\Fansworld\WebBundle\Entity\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\DoctrineBundle\Registry;

class EventMinuteFeeder {
    /** @var \Symfony\Bundle\DoctrineBundle\Registry */
    private $em;
    private $datafactory;

    public function __construct($em, $datafactory)
    {
        $this->em = $em;
        $this->datafactory = $datafactory;
    }
    
    public function feed()
    {
        $fichas = $this->datafactory->refresh('ficha');
        foreach ($fichas as $xml) {
            $this->processXml($xml);
        }
    }
    
    public function pending()
    {
        $pending = $this->datafactory->getPending('ficha');
        if ($pending) {
            $em = $this->em;
            foreach ($pending as $xmldata) {
                $xml = new \SimpleXMLElement($xmldata->getData());
                $this->processXml($xml);
                $xmldata->setProcessed(new \DateTime());
                $em->persist($xmldata);
            }
            $em->flush();
        }
    }
    
    private function processXml($xml)
    {
        $eventrepo = $this->em->getRepository('DodiciFansworldWebBundle:Event');
        $eventincrepo = $this->em->getRepository('DodiciFansworldWebBundle:EventIncident');
        
        $dfeventid = (string)$xml->fichapartido->attributes()->id;
        $event = $eventrepo->findOneByExternal($dfeventid);
        
        if ($event) {
            $hts = $event->getHasteams();
            $teamsext = array();
            foreach ($hts as $ht) $teamsext[$ht->getTeam()->getExternal()] = $ht->getTeam();
            
            $incs = $xml->xpath('fichapartido/incidencias/incidencia');
            $dfteams = $xml->xpath('fichapartido/equipo');
            $teams = array();
            foreach ($dfteams as $dfteam) {
                $teams[(string)$dfteam->nombreCorto] = $teamsext[(string)$dfteam->id];
            }
            
            foreach ($incs as $inc) {
                // Asumiendo que ID es único, y no "tipo"
                $dfincid = (string)$inc->attributes()->id;
                
                $incident = $eventincrepo->findOneBy(array('event' => $event->getId(), 'external' => $dfincid));
                
                if (!$incident) {
                    $incident = new EventIncident();
                    $incident->setExternal($dfincid);
                    
                    $team = $teams[(string)$inc->equiponomcorto];
                    $incident->setTeam($team);
                    
                    $event->addEventIncident($incident);
                    
                    $this->em->persist($event);
                    $this->em->flush();
                }
            }
        }
    }
    
}