<?php
namespace Dodici\Fansworld\WebBundle\Feeder;

use Dodici\Fansworld\WebBundle\Entity\EventIncident;
use Dodici\Fansworld\WebBundle\Entity\HasTeam;
use Dodici\Fansworld\WebBundle\Entity\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\DoctrineBundle\Registry;

/**
 * Gets match incidents from DataFactory, injects them into Events
 */
class EventMinuteFeeder {
    /** @var \Symfony\Bundle\DoctrineBundle\Registry */
    private $em;
    private $datafactory;
    
    const STATE_FINISHED = 2;

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
        $idolrepo = $this->em->getRepository('DodiciFansworldWebBundle:Idol');
        
        $dfeventid = (string)$xml->fichapartido->attributes()->id;
        $event = $eventrepo->findOneByExternal($dfeventid);
        
        if ($event) {
            $hts = $event->getHasteams();
            $teamsext = array();
            $hasteams = array();
            foreach ($hts as $ht) { 
                $teamsext[$ht->getTeam()->getExternal()] = $ht->getTeam();
                $hasteams[$ht->getTeam()->getId()] = $ht;
            }
            
            $incs = $xml->xpath('fichapartido/incidencias/incidencia');
            $state = (string)$xml->fichapartido->estadoEvento->attributes()->idestado;
            
            foreach ($incs as $inc) {
                // Asumiendo que ID es único, y no "tipo"
                $dfincid = (string)$inc->attributes()->id;
                $dfinctype = (string)$inc->attributes()->tipo;
                
                $incident = $eventincrepo->findOneBy(array('event' => $event->getId(), 'external' => $dfincid));
                
                $type = EventIncident::translateType($dfinctype);
                
                
                
                if (!$incident && $type) {
                    $incident = new EventIncident();
                    $incident->setExternal($dfincid);
                    
                    $type = EventIncident::translateType($dfinctype);
                    
                    $incident->setType($type);
                    
                    if (isset($inc->key->attributes()->id) && isset($teamsext[(string)$inc->key->attributes()->id])) {
                        $team = $teamsext[(string)$inc->key->attributes()->id];
                        $incident->setTeam($team);
                    }
                    
                    if (isset($inc->jugador->attributes()->id)) {
                        $playerext = (string)$inc->jugador->attributes()->id;
                        $idol = $idolrepo->findOneByExternal($playerext);
                        if ($idol) {
                            $incident->setIdol($idol);
                        } else {
                            $incident->setPlayername((string)$inc->jugador);
                        }
                    }
                    
                    $incident->setMinute((string)$inc->minuto);
                    $incident->setHalf((string)$inc->tiempo);
                    
                    $event->addEventIncident($incident);
                    
                    $this->em->persist($event);
                    
                    if ($type == EventIncident::TYPE_GOAL) {
                        $ht = $hasteams[$team->getId()];
                        $ht->setScore($ht->getScore() + 1);
                        $this->em->persist($ht);
                    }
                }
            }
            
            if ($state == self::STATE_FINISHED) {
                $event->setFinished(true);
                if (!$event->getTotime()) $event->setTotime(new \DateTime());
                $this->em->persist($event);
            }
            
            $this->em->flush();
        }
    }
    
}