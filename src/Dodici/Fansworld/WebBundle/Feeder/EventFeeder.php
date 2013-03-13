<?php
namespace Dodici\Fansworld\WebBundle\Feeder;

use Dodici\Fansworld\WebBundle\Entity\HasTeam;

use Dodici\Fansworld\WebBundle\Entity\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\DoctrineBundle\Registry;

/**
 * Gets matches from Datafactory's fixture, creates events from it
 */
class EventFeeder {
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
        $fixtures = $this->datafactory->refresh('fixture');
        foreach ($fixtures as $xml) {
            $this->processXml($xml);
        }
    }
    
    public function pending()
    {
        $pending = $this->datafactory->getPending('fixture');
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
        $result = $xml->xpath('fecha/partido');
        $teamrepo = $this->em->getRepository('Dodici\Fansworld\WebBundle\Entity\Team');
        
        $teamcatext = (string)$xml->categoria->attributes()->canal;
        
        foreach ($result as $xp) {
            $state = (string)$xp->estado->attributes()->id;
            $stadium = (string)$xp->attributes()->nombreEstadio;
            
            if ($state == 0) {
                $idlocal = (string)$xp->local->attributes()->id;
                $idaway = (string)$xp->visitante->attributes()->id;
                
                $localteam = $teamrepo->findOneBy(array('external' => $idlocal, 'active' => true));
                $awayteam = $teamrepo->findOneBy(array('external' => $idaway, 'active' => true));
                
                if ($localteam && $awayteam) {
                    $rawdate = (string)$xp->attributes()->fecha;
                    $rawhour = (string)$xp->attributes()->hora;
                    $date = null;
                    if ($rawdate) {
                        $date = \DateTime::createFromFormat('Ymd' . ($rawhour ? ' H:i:s' : ''), $rawdate . ($rawhour ? (' ' . $rawhour) : ''));
                    }
                    $xpexternal = (string)$xp->attributes()->id;
                    $event = $this->createEvent($date, $localteam, $awayteam, $xpexternal, $teamcatext, $stadium);
                    if ($event) $this->em->persist($event);
                }
            }
        }
        $this->em->flush();
    }
    
    private function createEvent($date, $localteam, $awayteam, $external, $teamcatext, $stadium)
    {
        $eventrepo = $this->em->getRepository('Dodici\Fansworld\WebBundle\Entity\Event');
        $event = $eventrepo->findOneByExternal($external);
        
        if (!$event) {
            $teamcategory = $this->em->getRepository('DodiciFansworldWebBundle:TeamCategory')->findOneByExternal($teamcatext);
            
            $event = new Event();
            $event->setExternal($external);
            $event->setTitle((string)$localteam . ' VS ' . (string)$awayteam);
            
            if ($teamcategory) $event->setTeamCategory($teamcategory);
            if ($stadium) $event->setStadium($stadium);
            
            $htl = new HasTeam();
            $htl->setTeam($localteam);
            $htl->setPosition(1);
            $event->addHasTeam($htl);
            
            $hta = new HasTeam();
            $hta->setTeam($awayteam);
            $hta->setPosition(2);
            $event->addHasTeam($hta);
        }
        
        if ($date) $event->setFromtime($date);
        
        return $event;
    }
}