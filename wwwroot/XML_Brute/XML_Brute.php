<?php

$config = parse_ini_file("config.ini.php");
$base = $config["BaseDir"];

require_once( $base."\\Builders\\BuilderFactory.php" );
require_once( $base."\\Helpers\\FileHelper.php" );
require_once( $base."\\Helpers\\MapHelper.php" );

use XML_Brute\Builders\BuilderFactory;
use XML_Brute\Helpers\FileHelper;	

// Copyright (c) 2017 David A Spitzley. All rights reserved.
// License: BSD 3-Clause License
// Website: https://github.com/dspitzle/XML_Brute

//Initialize global variable for tracking tables for storing 1-to-many subrecords
$GLOBALS["multi"] = array();

//Load the source XML file as a SimpleXML object
$fileInfo = FileHelper::get_Instance();
$xml = $fileInfo->importFile();
if ( $xml === FALSE ){
	echo "<h1>".$fileInfo->fullUploadedName." is not a valid XML file. Please inspect it with an XML validator such as <a href=\"https://xmlnotepad.codeplex.com/\">XML Notepad</a></h1>.";
}
else{
	//Construct a map of the data structure of the XML file
	echo " Mapping Data Structure...<br/>";
	flush();
	$tree = mapBranch( $xml );
	echo "<br/><pre>".print_r( $tree,true )."</pre><br/>";
	flush();

	//Create database to populate with contents of XML file
	$builder = BuilderFactory::build( $_POST["export_format"],$tree);
	$builder->build($tree);
	$builder->populate($xml);
	//PURGE OLD FILES FROM wwwroot downloads folder
	$builder->cleanUp();
	echo $builder->generateDownloadLink();
	
}
