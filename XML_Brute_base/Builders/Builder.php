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
	
	abstract function populate(\SimpleXMLElement $xml);
	
	function setFiles($ext){
		$this->fileInfo->ext = $ext;
		$this->fileInfo->fileName = $this->fileInfo->exportName.$this->fileInfo->ext;
		$this->fileInfo->buildLocation = $this->fileInfo->buildDir.$this->fileInfo->fileName;
		$this->fileInfo->finalLocation = "downloads\\".$this->fileInfo->fileName;
	}
	
}