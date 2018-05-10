<?php

/**
 * Generate an array representation of the data structure implied by the file structure,
 * flattened out to capture all variations between individual records
 * NOTE:  This function makes use of eval() numerous times.  This is a necessary evil due to
 * the $zoomName string serving as our representation of where in the XML document hierarchy we're zoomed in on
 * 
 * @param SimpleXMLElement $branch Current subsection of main document
 * @param array $mapZooms Array representation of the current location within $map
 * @param array $map Array representation of flattened data structure implied by source XML file
 * @param boolean $multiples Flag indicating whether $branch is an instance of a multi-instance level of the hierarchy
 * @param boolean $component Flag indicating whether $branch is a multi-element component
 *
 * @return array $map
 */	
function mapBranch( \SimpleXMLElement $branch, array $mapZooms=array(), array $map=null, $multiples=false, $component=false ){

	//This function is called recursively, with $mapZooms serving as a roving pointer into the data hierarchy

	$currentLayer = $branch->getName(); //Get the XML element name for the head of the current branch of the XML file
	$mapZooms[] = $currentLayer; //Stores the current XML element name in $mapZooms[]
	$zoomName = '$map'; //Initialize a string representation of the the node in $map referenced by the series of array indexes stored in $mapZooms[]

	//Construct a string representation of an multi-dimensional array key corresponding to the level of the XML document we're zoomed in on
	foreach ( $mapZooms as $zoomLayer ){
		$zoomName .= "[\"".$zoomLayer."\"]"; 
	}

	$zoomParentName = substr( $zoomName,0,strrpos( $zoomName,"[" ) );//Drop the last level name from $zoomName to get the string representation of the parent
	$multiZoomName = $zoomParentName."[\"".$currentLayer."__multi\"]";//Construct the multi-instance variant of $zoomName

	//If the current zoom level is a multi-instance element		
	if( $multiples and !$component ){
		$parentLayer = substr( $zoomParentName,strrpos( $zoomParentName,"[" )+2,-2 );//Grab parent name, minus wrapping brackets and quotes
		$GLOBALS["multi"][$currentLayer][$parentLayer]=true; //Mark the current zoom level as being a multiple in the $GLOBALS array ( indexed in "[child][parent]" order for ease of access later )
		eval("if(isset(".$zoomName.")){unset(".$zoomName.");}"); //If the current level exists in $map as a non-multi, remove it
		eval("if(!isset(".$multiZoomName.")){".$multiZoomName."=array('__MULTI'=>true);}"); //If the the multi-instance version of the current level has not been been added to $map yet, add it to $map as an array
	}
	else{
		eval( "if( !( isset( ".$zoomName." ) or isset( ".$multiZoomName." ) ) ){".$zoomName."=array();}" ); //If the current level has not been added to $map yet, add it to $map as an empty array
	}
	
	//If the current zoom level has attributes
	if( $branch->attributes()->count()>0 ){
		eval( "if( !isset( ".$zoomName."[\"".$currentLayer."__attributes\"] ) ){".$zoomName."[\"".$currentLayer."__attributes\"]=array();}" ); //Set the corresponding attributes $mapZooms entry to an array if it isn't already set		

		//For each attribute set the corresponding attribute's $mapZooms entry to true ( as a placeholder )
		foreach( $branch->attributes() as $attr ){
			eval( $zoomName."[\"".$currentLayer."__attributes\"][\"".$attr->getName()."\"]=true;" );
		}
	}
	
	//If the current level has children
	if( $branch->count()>0 ){
		$childListMultiples = array();
		$childListComponent = array();
		foreach( $branch->children() as $child ){//For each child determine whether it has multiple instances
			$childName = $child->getName();

			//If this child's name has already been seen
			if ( isset( $childListMultiples[$childName] ) ){
				$childListMultiples[$childName]=true;//update multiple flag for the level to true
			}
			else{
				$childListMultiples[$childName]=false;//initialize multiple flag for the level to false
			}

			//If this child has its own children				
			if ( $child->count()>0 ){
				$childListComponent[$childName]=true;//set or update component flag for the level to true
			}
			elseif ( !isset( $childListComponent[$childName] ) ){//otherwise, if the child hasn't been seen before
				$childListComponent[$childName]=false;//initialize component flag for the level to false
			}
		}
		
		//For each child make a recursive call to this function
		foreach( $branch->children() as $child ){
			$childName = $child->getName();
			$map = mapBranch( $child,$mapZooms,$map,$childListMultiples[$childName],$childListComponent[$childName] );
		}
	}
	return $map;
}
