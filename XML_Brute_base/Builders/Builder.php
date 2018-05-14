<?php

namespace XML_Brute\Builders;

require_once($base."\\Helpers\\FileHelper.php");

use XML_Brute\Helpers\FileHelper;

abstract class Builder{

	protected $fileInfo = null;
	protected $tree = null;	
	protected $connection = null;	
	
	protected function __construct(){
		$this->fileInfo = FileHelper::get_Instance();
	}
	
	abstract function build();

	function generateDownloadLink(){
		$this->connection = null;
		rename ( $this->fileInfo->buildLocation , $this->fileInfo->finalLocation  );
		return "<a href=\"".$this->fileInfo->finalLocation."\">Download ".$this->fileInfo->fileName."</a>";
	}

	
	function cleanUp(){
		unlink($this->fileInfo->uploadTarget);
		$downloads = 'downloads';
		$fileSystemIterator = new \FilesystemIterator('downloads');
		$now = time();
		foreach ($fileSystemIterator as $file) {
			//echo "Checking ".$file->getFilename()."<br/>";
			if (($now - $file->getCTime() >= 60 * 15) and ($file->getFilename() != 'stub.txt')){ // 15 minutes, and not the placeholder file
				//echo "Removing ".$file->getFilename()."<br/>";			
				unlink('downloads\\'.$file->getFilename());
			}
		}		
	}
	
	
	abstract function populate(\SimpleXMLElement $xml);
	
	function setFiles($ext){
		$this->fileInfo->ext = $ext;
		$this->fileInfo->fileName = $this->fileInfo->exportName.$this->fileInfo->ext;
		$this->fileInfo->buildLocation = $this->fileInfo->buildDir.$this->fileInfo->fileName;
		$this->fileInfo->finalLocation = "downloads\\".$this->fileInfo->fileName;
	}
	
}