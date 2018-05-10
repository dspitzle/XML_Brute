<?php
	
namespace XML_Brute\Builders;

require_once($base."\\Builders\\Builder.php");

use XML_Brute\Builders\Builder;

class Accdb32Builder extends Builder{


	public function __construct( array $tree ){
		parent::__construct();
		$this->tree = $tree;
		$this->setFiles(".accdb");		
	}

	
	public function build(){
		echo "Creating Fresh ".$this->fileInfo->ext." File from Template...<br/>";
		echo "Copying '".realpath("./")."\\Templates\\DatabaseTemplate".$this->fileInfo->ext."' to '".realpath( "./" ).'\\'.$this->fileInfo->buildLocation."'...<br/>";
		flush();
		copy ( $this->fileInfo->base."\\Templates\\DatabaseTemplate".$this->fileInfo->ext , $this->fileInfo->buildLocation  );
		echo 'PDO Drivers Available: '.print_r(\PDO::getAvailableDrivers(),true).'<br/>';
		$connectionString = "odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=".$this->fileInfo->buildLocation.";Uid=Admin";
		echo "PDO Connecting to: ".$connectionString."<br/><br/>";			
		$this->connection = new \PDO($connectionString);
		//Construct tables in target MS Access database
		echo "Constructing Data Tables...<br/>";
		flush();
		$this->buildAccdbDB( $this->tree );		
	}


	/**
	 * Populate an MS Access .accdb database with tables representing the data structure $tree
	 * 
	 * @param PDO $connection Connection to target MS Access file
	 * @param array $tree Array representation of flattened data structure implied by source XML file
	 * @return string $fieldsList
	 */	
	private function buildAccdbDB( array $tree, $parent="" ){
		$fieldsList = "";
		
		//For each entry in the hierarchy map, separate the entry from anything branching off of it
		foreach( $tree as $branch=>$leaf ){
			
			//If the entry has any children 
			if( is_array( $leaf ) and count( $leaf )>0 ){
				$table = $branch;
				
				//If the table is for storing repeated elements
				if( substr( $branch,-7 )=="__multi" ){
					$table = $parent."__".$branch;//prepend the table name with the name of the branch's parent
				}
				
				//If the parent and child levels have the same name, double them up for the table name
				if ( $parent<>"" and $table == $parent ){
					$table .= "__".$table;
				}
				
				$createAccessFields = "CREATE TABLE ".$table." ( __ID AUTOINCREMENT PRIMARY KEY, __TENTPOLE YESNO"; //initiate a new CREATE TABLE command
				$createAccessFields .= $this->buildAccdbDB( $leaf, $branch );//Make a recursive call to this function to get the fields
				
				//If this new table is a child of another table
				if( $parent<>"" ){
					$createAccessFields .= ", ".$parent."__ID INTEGER";//Add a field for the foreign key
				}
				
				//If the table is for storing repeated elements
				if( substr( $branch,-7 )=="__multi" ){
					$createAccessFields .= ", ".substr( $branch,0,-7 )." TEXT( 255 )";//Add a field for the repeated elements content
				}
				
				$createAccessFields .= " )";//Close CREATE TABLE command
				echo $createAccessFields."<br/><br/>";
				flush();				
				$affected = $this->connection->exec( $createAccessFields );//Execute CREATE TABLE command
				
				//If the CREATE TABLE query had no effect display error message to user_error
				if( $affected === FALSE ) {
					echo "<pre>".print_r( $this->connection->errorInfo() )."</pre><p>See <a href='http://www.ibm.com/support/knowledgecenter/SSGU8G_11.70.0/com.ibm.sqls.doc/ids_sqs_0809.htm'>SQLSTATE Codes list</a> for clarification</p>";
					flush();
				}
			}
			else{//otherwise, if there are no children
				$fieldsList .= ", ".$branch." TEXT( 255 )";//Append the field name to the list to be included in the parent CREATE TABLE command
			}
		}
		return $fieldsList;//Return the fields list ( if any ) for inclusion in any parent CREATE TABLE command
	}
	

	public function populate( \SimpleXMLElement $xml ){
		echo "Populating Data Tables...<br/>";
		flush();
		$multis = $GLOBALS["multi"];
		echo "<pre>Multis: ".print_r( $multis,true )."</pre><br/>";
		$this->connection->beginTransaction();
		$this->populateAccdbDB( $xml );
		$this->connection->commit();
	}	
	
	
	private	function populateAccdbDB( \SimpleXMLElement $branch, $indent = "", $parent="", $parent_id = null ){
		$level = $branch->getName();
		$table = $level; //Current level in hierarchy sets target table for writing data
		
		//If the target table has a parent of the same name, double up the name
		if( $parent <> "" and $table == $parent ){
			$table .= "__".$table;
		}
		
		//If $branch has children or is a multi-instance element
		if( $branch->count()>0 or isset( $GLOBALS["multi"][$level][$parent] ) ){

			//Initialize the variables used to construct the INSERT statement
			$insertAccessFields = "[__TENTPOLE], ";
			$insertAccessPlaceholders = "?, ";
			$insertAccessValues = array( TRUE );

			foreach( $branch->children() as $child ){
				if( $child->count()==0 and !isset( $GLOBALS["multi"][$child->getName()][$level] ) ){//If the child has no children and is not a multi-instance element
					$field = $this->populateAccdbDB( $child,$indent."-" );//make a recursive call to this function
					foreach( $field as $key=>$value ){//add each recursively returned field to the INSERT variables
						$insertAccessFields .= "[".$key."], ";
						$insertAccessPlaceholders .= "?, ";										
						$insertAccessValues[] = $value;
					}
				}
			}

			//If this branch has a foreign key
			if( $parent<>"" ){
			
				//Append parent__ID data to the INSERT variables
				$insertAccessFields .= "[".$parent."__ID], ";
				$insertAccessPlaceholders .= "?, ";				
				$insertAccessValues[] = $parent_id;

				//If the parent table has the current branch as a multi-instance child
				if( isset( $GLOBALS["multi"][$level][$parent] ) ){
					$insertAccessFields .= "[".$level."], ";//Append repeated element field
					$insertAccessPlaceholders .= "?, ";				
					$insertAccessValues[] = $branch->__toString();//Append repeated element value
					$table = $parent."__".$level."__multi";
				}
			}
			$insertAccessFields = substr( $insertAccessFields,0,-2 );//Prune final ", " combo from fields list
			$insertAccessPlaceholders = substr( $insertAccessPlaceholders,0,-2 );//Prune final ", " combo from placeholders list
			$insertQuery = $this->connection->prepare( "INSERT INTO [".$table."] ( ".$insertAccessFields." ) VALUES ( ".$insertAccessPlaceholders." )" );
			$success = $insertQuery->execute( $insertAccessValues );
			$maxQuery = $this->connection->prepare( "SELECT MAX( [__ID] ) AS [ID] FROM [".$table."]" );			
			$maxQuery->execute();
			$max = $maxQuery->fetch();
			$insertID = $max["ID"];
			echo $indent.$level." ".$insertID."<br/>";
			flush();			

			foreach( $branch->children() as $child ){//For each child 
				if( $child->count()>0 or isset( $GLOBALS["multi"][$child->getName()][$level] ) ){//If the child has children or is a solitary multi-instance element
					$this->populateAccdbDB( $child,$indent."-",$level,$insertID );//make a recursive call to this function
				}
			}
		}

		//If the XML element at the current location has attributes, store them
		if( $branch->attributes()->count()>0 ){ 
			$this->storeAttributes($level, $branch, $insertID );
		}

		//If the branch has no children, return the value of the terminal XML element
		if( $branch->count()==0 ){
			return array( $level=>$branch->__toString() );
		}
	}
	
	
	private function storeAttributes( $level, \SimpleXMLElement $branch, $insertID ){
		$insertAccessFields = "INSERT INTO [".$level."__attributes] ( __TENTPOLE, "; //initiate a new INSERT INTO command
		$insertAccessPlaceholders = " VALUES ( ?, ";
		$insertAccessValues = array( TRUE );
		foreach( $branch->attributes() as $attr=>$attrValue ){//For each attribute insert the value into the appropriate field
			$insertAccessFields .= "[".$attr."], ";
			$insertAccessPlaceholders .= "?, ";	
			$insertAccessValues[] = $attrValue;
		}
		$insertAccessFields .= "[".$level."__ID] )";//Append parent__ID field and closing parentheses
		$insertAccessPlaceholders .= "? )";//Prepare parent_id placeholder and closing parentheses
		$insertAccessValues[] = $insertID;//Append parent_id value
		$insertQuery = $this->connection->prepare( $insertAccessFields.$insertAccessPlaceholders );			
		$success = $insertQuery->execute( $insertAccessValues );
		//echo $level."__attributes: ".$insertAccessFields.$insertAccessPlaceholders."<br/>";
		//flush();				
	}
	
	
}