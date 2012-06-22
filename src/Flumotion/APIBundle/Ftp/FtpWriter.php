<?php
namespace Flumotion\APIBundle\Ftp;

use Gaufrette\Adapter\Ftp;

class FtpWriter {
    
    private $api;
    private $ftp;

    public function __construct($api, $host, $path, $file, $user, $pass)
    {
        $this->api = $api;
        $this->file = $file;
        $this->ftp = new Ftp($path, $host, $user, $pass, 21, true, false, FTP_BINARY);
    }
    
	public function writeCalendarFile($blogs)
    {
    	return $this->ftp->write($this->file, $this->api->getCalendar($blogs));
    }
    
    public function writeFile($filename, $binarycontent)
    {
        return $this->ftp->write($filename, $binarycontent);
    }
    
}