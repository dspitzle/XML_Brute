<?php

namespace XML_Brute\Helpers;

/**
* A Singleton class that provides directory information, 
* and moves and imports files
*/
class FileHelper{

	public $base = "";
	public $uploadDir = "";
	public $buildDir = "";
	public $fullUploadedName = "";
	public $tmp_name = "";	
	public $uploadedName = "";
	public $targetFile = "";
	public $exportName = "";
	public $ext = "";	
    public $fileName = "";
	public $buildLocation = "";
	public $finalLocation = "";
	static $_instance;
	
	
	private function __construct(){
		$config = parse_ini_file("config.ini.php");
		$this->base = $config["BaseDir"];
		$this->uploadDir = $this->base.'\\storage\\uploads\\';
		$this->buildDir = $this->base.'\\storage\\downloads\\';
		
		$this->fullUploadedName = $_FILES['target_file']['name'];
		$this->tmp_name = $_FILES["target_file"]["tmp_name"];	
		( strrpos( $this->fullUploadedName, '.' ) !== FALSE ) ? $this->uploadedName = substr( $this->fullUploadedName, 0, strrpos( $this->fullUploadedName,'.' ) ) : $this->uploadedName = $this->fullUploadedName;
		$this->uploadTarget = $this->uploadDir.$this->fullUploadedName;
		$this->exportName = $this->uploadedName.'-'.date( 'YmdHis' );
	}

	
	public static function get_Instance(){
		if ( !(self::$_instance instanceof self) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	
	public function importFile(){
		echo "Connecting to Data File '".$this->fullUploadedName."'...<br/>";
		$mime_type = mime_content_type($_FILES["target_file"]["tmp_name"]);		
		if ( substr($mime_type,-3)!="xml"){
			throw new Exception("Uploaded file is a non-XML format: ".$mime_type);
		}
		else{
			echo "Mime type = ".mime_content_type($_FILES["target_file"]["tmp_name"])."<br/>";
		}
		flush();
		move_uploaded_file( $this->tmp_name, $this->uploadTarget );
		return simplexml_load_file( $this->uploadTarget );
	}
}