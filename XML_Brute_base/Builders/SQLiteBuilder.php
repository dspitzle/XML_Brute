<?php
	
namespace XML_Brute\Builders;

require_once($base."\\Builders\\Builder.php");

use XML_Brute\Builders\Builder;

class SQLiteBuilder extends Builder{


	public function __construct( array $tree ){
		parent::__construct();
		$this->tree = $tree;
		$this->setFiles(".db");
	}

	
	public function build(){
		echo 'PDO Drivers Available: '.print_r(\PDO::getAvailableDrivers(),true).'<br/>';
		$connectionString = "sqlite:".$this->fileInfo->buildLocation;
		echo "PDO Connecting to: '\\storage\\downloads\\".$this->fileInfo->fileName."'<br/><br/>";			
		$this->connection = new \PDO($connectionString);
		//Construct tables in target SQLite database
		echo "Constructing Data Tables...<br/>";
		flush();
		$this->buildSQLiteDB( $this->tree );	
	}


	/**
	 * Populate an SQLite .db database with tables representing the data structure $tree
	 * 
	 * @param PDO $connection Connection to target SQLite file
	 * @param array $tree Array representation of flattened data structure implied by source XML file
	 * @return string $fieldsList
	 */	
	private function buildSQLiteDB( array $tree, $parent="" ){
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
				
				$createSQLiteFields = "CREATE TABLE ".$table." ( __ID INTEGER PRIMARY KEY AUTOINCREMENT, __TENTPOLE INTEGER"; //initiate a new CREATE TABLE command
				$createSQLiteFields .= $this->buildSQLiteDB( $leaf, $branch );//Make a recursive call to this function to get the fields
				
				//If this new table is a child of another table
				if( $parent<>"" ){
					$createSQLiteFields .= ", ".$parent."__ID INTEGER";//Add a field for the foreign key
				}
				
				//If the table is for storing repeated elements
				if( substr( $branch,-7 )=="__multi" ){
					$createSQLiteFields .= ", ".substr( $branch,0,-7 )." TEXT";//Add a field for the repeated elements content
				}
				
				$createSQLiteFields .= " )";//Close CREATE TABLE command
				echo $createSQLiteFields."<br/><br/>";
				flush();				
				$affected = $this->connection->exec( $createSQLiteFields );//Execute CREATE TABLE command
				
				//If the CREATE TABLE query had no effect display error message to user_error
				if( $affected === FALSE ) {
					echo "<pre>".print_r( $this->connection->errorInfo(), true )."</pre><p>See <a href='https://www.sqlite.org/c3ref/c_abort.html'>SQLite Result Codes List</a> for clarification</p>";
					flush();
				}
			}
			else{//otherwise, if there are no children
				$fieldsList .= ", ".$branch." TEXT";//Append the field name to the list to be included in the parent CREATE TABLE command
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
		$this->populateSQLiteDB( $xml );
		$this->connection->commit();
	}	
	
	
	private	function populateSQLiteDB( \SimpleXMLElement $branch, $indent = "", $parent="", $parent_id = null ){
		$level = $branch->getName();
		$table = $level; //Current level in hierarchy sets target table for writing data
		
		//If the target table has a parent of the same name, double up the name
		if( $parent <> "" and $table == $parent ){
			$table .= "__".$table;
		}
		
		//If $branch has children or is a multi-instance element
		if( $branch->count()>0 or isset( $GLOBALS["multi"][$level][$parent] ) ){

			//Initialize the variables used to construct the INSERT statement
			$insertSQLiteFields = "[__TENTPOLE], ";
			$insertSQLitePlaceholders = "?, ";
			$insertSQLiteValues = array( TRUE );

			foreach( $branch->children() as $child ){
				if( $child->count()==0 and !isset( $GLOBALS["multi"][$child->getName()][$level] ) ){//If the child has no children and is not a multi-instance element
					$field = $this->populateSQLiteDB( $child,$indent."-" );//make a recursive call to this function
					foreach( $field as $key=>$value ){//add each recursively returned field to the INSERT variables
						$insertSQLiteFields .= "[".$key."], ";
						$insertSQLitePlaceholders .= "?, ";										
						$insertSQLiteValues[] = $value;
					}
				}
			}

			//If this branch has a foreign key
			if( $parent<>"" ){
			
				//Append parent__ID data to the INSERT variables
				$insertSQLiteFields .= "[".$parent."__ID], ";
				$insertSQLitePlaceholders .= "?, ";				
				$insertSQLiteValues[] = $parent_id;

				//If the parent table has the current branch as a multi-instance child
				if( isset( $GLOBALS["multi"][$level][$parent] ) ){
					$insertSQLiteFields .= "[".$level."], ";//Append repeated element field
					$insertSQLitePlaceholders .= "?, ";				
					$insertSQLiteValues[] = $branch->__toString();//Append repeated element value
					$table = $parent."__".$level."__multi";
				}
			}
			$insertSQLiteFields = substr( $insertSQLiteFields,0,-2 );//Prune final ", " combo from fields list
			$insertSQLitePlaceholders = substr( $insertSQLitePlaceholders,0,-2 );//Prune final ", " combo from placeholders list
			$insertQuery = $this->connection->prepare( "INSERT INTO [".$table."] ( ".$insertSQLiteFields." ) VALUES ( ".$insertSQLitePlaceholders." )" );
			$success = $insertQuery->execute( $insertSQLiteValues );
			$maxQuery = $this->connection->prepare( "SELECT MAX( [__ID] ) AS [ID] FROM [".$table."]" );			
			$maxQuery->execute();
			$max = $maxQuery->fetch();
			$insertID = $max["ID"];
			echo $indent.$level." ".$insertID."<br/>";
			flush();			

			foreach( $branch->children() as $child ){//For each child 
				if( $child->count()>0 or isset( $GLOBALS["multi"][$child->getName()][$level] ) ){//If the child has children or is a solitary multi-instance element
					$this->populateSQLiteDB( $child,$indent."-",$level,$insertID );//make a recursive call to this function
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
		$insertSQLiteFields = "INSERT INTO [".$level."__attributes] ( __TENTPOLE, "; //initiate a new INSERT INTO command
		$insertSQLitePlaceholders = " VALUES ( ?, ";
		$insertSQLiteValues = array( TRUE );
		foreach( $branch->attributes() as $attr=>$attrValue ){//For each attribute insert the value into the appropriate field
			$insertSQLiteFields .= "[".$attr."], ";
			$insertSQLitePlaceholders .= "?, ";	
			$insertSQLiteValues[] = $attrValue;
		}
		$insertSQLiteFields .= "[".$level."__ID] )";//Append parent__ID field and closing parentheses
		$insertSQLitePlaceholders .= "? )";//Prepare parent_id placeholder and closing parentheses
		$insertSQLiteValues[] = $insertID;//Append parent_id value
		$insertQuery = $this->connection->prepare( $insertSQLiteFields.$insertSQLitePlaceholders );			
		$success = $insertQuery->execute( $insertSQLiteValues );
		//echo $level."__attributes: ".$insertSQLiteFields.$insertSQLitePlaceholders."<br/>";
		//flush();				
	}
	
	
}