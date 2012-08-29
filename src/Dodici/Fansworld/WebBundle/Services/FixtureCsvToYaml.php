<?php

namespace Dodici\Fansworld\WebBundle\Services;


/**
 * Convert CSV into YAML fixtures
 */
class FixtureCsvToYaml
{
	public function convertAll()
	{
	    $names = array('sports', 'teamcategories', 'teams', 'idols');
	    
	    foreach ($names as $name) $this->convert($name);
	}
	
	public function convert($name)
	{
	    $ymlpath = __DIR__ . '/../DataFixtures/';
	    
	    $csv = $this->getCSV($name);
        $ymlfilename = $ymlpath . $name . '.yml';
        
        $c = 0;
        $yml = '';
        
        foreach($csv as $data) {
            if ($c++ && $data[0]) {
                $yml .= $this->{'node'.ucfirst($name)}($data);
                $yml .= "\n";
            }
        }
        
        file_put_contents($ymlfilename, $yml);
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
    
    private function nodeSports($data)
    {
        if (!$data[0]) return null;
        
        $yml  = "-\n";
        $yml .= "  id: "    . utf8_encode($data[0]) . "\n";
        $yml .= "  title: " . utf8_encode($data[1]);
        return $yml;
    }
    
    private function nodeTeamcategories($data)
    {
        if (!$data[0]) return null;
        
        $yml  = "-\n";
        $yml .= "  id: "    . utf8_encode($data[0]) . "\n";
        $yml .= "  sport: " . utf8_encode($data[1]) . "\n";
        $yml .= "  title: " . utf8_encode($data[2]);
        
        return $yml;
    }
    
    private function nodeTeams($data)
    {
        if (!$data[0]) return null;
        
        $yml  = "-\n";
        $yml .= "  id: " . utf8_encode($data[0]) . "\n";
        $yml .= "  teamcategory: " . utf8_encode($data[10]) . "\n";
        $yml .= "  title: " . utf8_encode($data[1]) . "\n";
        
        $date = $data[2];
        if ($date) {
            if (strpos($date, '/') !== false) {
                $xp = explode('/', $date);
                $datestr = $xp[2].'-'.sprintf('%02d',$xp[1]).'-'.sprintf('%02d',$xp[0]);
            } else {
                $datestr = $date.'-01-01';
            }
            $yml .= "  foundedAt: \""    . $datestr . "\"\n";
        }
        
        $yml .= "  nicknames: " . utf8_encode($data[3]) . "\n";
        $yml .= "  letters: "   . utf8_encode($data[4]) . "\n";
        $yml .= "  shortname: " . utf8_encode($data[5]) . "\n";
        $yml .= "  stadium: "   . utf8_encode($data[6]) . "\n";
        $yml .= "  website: "   . utf8_encode($data[7]) . "\n";
        $yml .= "  twitter: "   . utf8_encode($data[8]) . "\n";
        if ($data[11]) $yml .= "  country: " . utf8_encode($data[11]) . "\n";
        if ($data[9]) $yml .= "  external: " . utf8_encode($data[9]) . "\n";
        
        $desc = $data[12];
        if ($desc) {
            $xp = explode("\n", $desc);
            $yml .= "  content: |\n"; 
            foreach ($xp as $x) {
                $yml .= "    ".utf8_encode($x)."\n";
            }
        }
        
        return $yml;
    }
    
    private function nodeIdols($data)
    {
        if (!$data[0]) return null;
        
        $yml  = "-\n";
        $yml .= "  id: " . utf8_encode($data[0]) . "\n";
        $yml .= "  firstname: " . utf8_encode($data[1]) . "\n";
        $yml .= "  lastname: " . utf8_encode($data[2]) . "\n";
        
        
        $yml .= "  jobname: " . utf8_encode($data[11]) . "\n";
        $yml .= "  nicknames: " . utf8_encode($data[12]) . "\n";
        
        $date = $data[13];
        if ($date) {
            if (strpos($date, '/') !== false) {
                $xp = explode('/', $date);
                $datestr = $xp[2].'-'.sprintf('%02d',$xp[1]).'-'.sprintf('%02d',$xp[0]);
            } else {
                $datestr = $date.'-01-01';
            }
            $yml .= "  birthday: \""    . $datestr . "\"\n";
        }
        
        if ($data[14]) $yml .= "  country: " . utf8_encode($data[14]) . "\n";
        $yml .= "  twitter: " . utf8_encode($data[15]) . "\n";
        
        $desc = $data[16];
        if ($desc) {
            $xp = explode("\n", $desc);
            $yml .= "  content: |\n"; 
            foreach ($xp as $x) {
                $yml .= "    ".utf8_encode($x)."\n";
            }
        }
                
        $ic_ids['players'] = $data[3] ? explode("\n", $data[3]) : null;
        $ic_debut['players'] = $data[4] ? explode("\n", $data[4]) : null;
        $ic_actual['players'] = $data[5] ? explode("\n", $data[5]) : null;
        $ic_highlight['players'] = $data[6] ? explode("\n", $data[6]) : null;
        
        $ic_ids['managers'] = $data[7] ? explode("\n", $data[7]) : null;
        $ic_debut['managers'] = $data[8] ? explode("\n", $data[8]) : null;
        $ic_actual['managers'] = $data[9] ? explode("\n", $data[9]) : null;
        $ic_highlight['managers'] = $data[10] ? explode("\n", $data[10]) : null;
        
        $first = false;
        
        foreach ($ic_ids as $key => $vals) {
            $manager = ($key == 'managers') ? true : false;
            
            if ($vals) {
                foreach ($vals as $index => $id) {
                    if ($id) {
                        $debut = (($ic_debut[$key][$index] == 1) ? true : false);
                        $actual = (($ic_actual[$key][$index] == 1) ? true : false);
                        $highlight = (($ic_highlight[$key][$index] == 1) ? true : false);
                        
                        if (!$first) {
                            $yml .= "  teams:\n";
                            $first = true;
                        }
                        
                        $yml .= "      -\n";
                        
                        if (is_numeric($id)) {
                            $yml .= "        id: " . $id . "\n";
                        } else {
                            $yml .= "        name: " . $id . "\n";
                        }
                        
                        $yml .= "        debut: " . ($debut ? 'true' : 'false') . "\n";
                        $yml .= "        actual: " . ($actual ? 'true' : 'false') . "\n";
                        $yml .= "        highlight: " . ($highlight ? 'true' : 'false') . "\n";
                        $yml .= "        manager: " . ($manager ? 'true' : 'false') . "\n";
                    }
                }
            }
        }
        
        return $yml;
    }
}