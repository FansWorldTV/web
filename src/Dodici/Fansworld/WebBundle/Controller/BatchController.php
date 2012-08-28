<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Batch controller.
 * @Route("/batch")
 */
class BatchController extends SiteController
{

    /**
     * Feed the Event fixture
     * @Route("/eventfeeding", name= "admin_batch_eventfeeding")
     */
    public function eventFeedingAction()
    {
        $df = $this->get('feeder.event');
        $df->feed();
		$df->pending();
		
		return new Response('Ok');
    }
    
	/**
     * Feed event incidents
     * @Route("/eventminutefeeding", name= "admin_batch_eventminutefeeding")
     */
    public function eventMinuteFeedingAction()
    {
        $df = $this->get('feeder.event.minute');
        $df->feed();
		$df->pending();
		
		return new Response('Ok');
    }

	/**
     * Process pending videos (thumbnail, upload, etc)
     * @Route("/videoprocessing", name= "admin_batch_videoprocessing")
     */
    public function videoProcessingAction()
    {
        $videos = $this->getRepository('Video')->pendingProcessing(10);
        $uploader = $this->get('video.uploader');
        
        foreach ($videos as $video) {
            $uploader->process($video);
        }
        
        return new Response('Ok');
    }
    
	/**
     * Clean up timed out users from "watching video" lists
     * @Route("/videoaudienceclean", name= "admin_batch_videoaudienceclean")
     */
    public function videoAudienceCleanAction()
    {
        $this->get('video.audience')->cleanup();
        
        return new Response('Ok');
    }
    
    /**
     * Convert CSV fixture files to YML
     * Ask before running
     * @Route("/fixturecsvtoyml", name="admin_batch_csvtoyml")
     */
    public function convertCSVtoYML()
    {
        $names = array('sports', 'teamcategories', 'teams');
        $ymlpath = __DIR__ . '/../DataFixtures/';
        
        foreach ($names as $name) {
            $csv = $this->getCSV($name);
            $ymlfilename = $ymlpath . $name . '.yml';
            
            $c = 0;
            $yml = '';
            
            var_dump($csv->count());
            foreach($csv as $data) {
                if ($c++) {
                    switch ($name) {
                        case 'sports':
                            $yml .= "-\n";
                            $yml .= "  id: "    . utf8_encode($data[0]) . "\n";
                            $yml .= "  title: " . utf8_encode($data[1]);
                            break;
                        case 'teamcategories':
                            $yml .= "-\n";
                            $yml .= "  id: "       . utf8_encode($data[0]) . "\n";
                            $yml .= "  sport: "    . utf8_encode($data[1]) . "\n";
                            $yml .= "  title: "    . utf8_encode($data[2]);
                            break;
                        case 'teams':
                            $yml .= "-\n";
                            $yml .= "  id: "    . utf8_encode($data[0]) . "\n";
                            $yml .= "  teamcategory: "    . utf8_encode($data[10]) . "\n";
                            $yml .= "  title: "    . utf8_encode($data[1]) . "\n";
                            
                            $date = $data[2];
                            if ($date) {
                                if (strpos($date, '/') !== false) {
                                    $xp = explode('/', $date);
                                    $datestr = $xp[2].'-'.$xp[1].'-'.$xp[0];
                                } else {
                                    $datestr = $date.'-01-01';
                                }
                                $yml .= "  foundedAt: "    . $datestr . "\n";
                            }
                            
                            $yml .= "  nicknames: "    . utf8_encode($data[3]) . "\n";
                            $yml .= "  letters: "    . utf8_encode($data[4]) . "\n";
                            $yml .= "  shortname: "    . utf8_encode($data[5]) . "\n";
                            $yml .= "  stadium: "    . utf8_encode($data[6]) . "\n";
                            $yml .= "  website: "    . utf8_encode($data[7]) . "\n";
                            $yml .= "  twitter: "    . utf8_encode($data[8]) . "\n";
                            if ($data[11]) $yml .= "  country: "    . utf8_encode($data[11]) . "\n";
                            if ($data[9]) $yml .= "  external: "    . utf8_encode($data[9]) . "\n";
                            
                            $desc = $data[12];
                            if ($desc) {
                                $xp = explode("\n", $desc);
                                $yml .= "  content: |\n"; 
                                foreach ($xp as $x) {
                                    $yml .= "  ".utf8_encode($x)."\n";
                                }
                            }
                            break;
                        case 'idols':
                            // TODO: idols
                            break;
                    }
                    
                    $yml .= "\n";
                }
            }
            
            file_put_contents($ymlfilename, $yml);
        }
        
        return new Response('Ok');
    }
    
    private function getCSV($name)
    {
        $resource = __DIR__ . '/../DataFixtures/csv/'.$name.'.csv';
        
        try {
            $file = new \SplFileObject($resource, 'rb');
        } catch(\RuntimeException $e) {
            throw new \InvalidArgumentException(sprintf('Error opening file "%s".', $resource));
        }
        
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(';', '"', '\\');
        
        return $file;
    }
}
