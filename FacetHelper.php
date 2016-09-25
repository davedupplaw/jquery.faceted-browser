<?php
	/**
	 *	Class for helping to generate SQL statements for
	 *	Facets from the facet browser:
	 *	http://david.dupplaw.me.uk/developer/jquery-facets
	 *
	 *	Class built from some older code I had lying around.
	 *	Currently this class constructs single-table queries
	 *	based around the facet map that's supplied in the 
	 *	constructor.
	 *
	 *	Uses RedBeanPHP ORM for database access. To use
	 *	add RedBeanPHP into your composer.json, update,
	 *	then ensure RedBeanPHP is autoloaded prior to including
	 *	this class. Alternatively, include the single-file
	 *	RedBeanPHP class. You must also setup the RedBeanPHP
	 *	ORM prior to using this, as this class assumes the
	 *	database connection has already been setup (with R::setup)
	 *
	 *	@author David Dupplaw <david@dupplaw.me.uk>
	 *	@created 22nd November 2013
	 */
	use RedBean_Facade as R;

	class FacetHelper
	{
		/**
		 *	Construct a facet helper using the given
		 *	map. If no map is given, an empty map will
		 *	be used. The map maps facet names to column
		 *	names in the table being browsed.
		 *
		 *	@param $table The name of the table being browsed
		 *	@param array $map The map
		 */
		public function __construct( $table = "table", array $map = array() )
		{
			$this->table = $table;
			$this->map = $map;
			$this->calculateInverseMap();
		}

		/**
		 *	Inverts the map for indexing purposes. That is,
		 *	it creates a map from table name to facet name.
		 *	Requires the $map property to be filled.
		 *	Side-affects the $invMap property.
		 */
		private function calculateInverseMap()
		{
			// Calculate an inverse map
			$this->invMap = array();
			foreach( $this->map as $k=>$v )
				$this->invMap[$v] = $k;
		}

		/**
		 *	Returns the WHERE clause with values replaced by question marks
		 *	and an array providing the values (for prepared statement creation)
		 *	that specify the set of results for the specific selected facets
		 *	and facet values.
		 *
		 *	@param array $facets The facet array (see getFacetList())
		 *	@return A map with keys "statement" and "values" where "statement"
		 *		is a string containing the WHERE statement and "values"
		 *		is an array containing the values
		 */
		public function getSQLWhereClauseAndArray( $facets )
		{
			// Loop through the facets in order and create a WHERE clause that we can reuse
			// Note that this throws an exception if the facet isn't mapped in the database.
			$whereClause = "";
			$whereArray = array();
			$first = true;
			for( $i = 0; $i < count($facets); $i++ )
			{
				// If there is no mapping for the facet, there's an error somewhere.
				if( !isset( $this->map[$facets[$i]["facet"]] ) )
					throw new Exception( "Facet ".($i+1)." (".$facets[$i]["facet"]
						.") is not a valid facet name." );
				
				// We don't need to escape the facet value here because we'll be passing
				// it via a prepared statement to RedBeanPHP.
				$facet = $this->map[$facets[$i]["facet"]];
				$value = $facets[$i]["value"];

				// Put AND between the clauses
				if( !$first )
					$whereClause .= "AND ";
				
				// Put the value as a clause (or 1 if there's no value)
				if( $value != "" )
				{
					$whereClause .= "`".$facet."` = ? ";
					$whereArray[] = $value;
				}
				else	$whereClause .= "1 ";
				
				$first = false;
			}
	
			return array( "statement" => $whereClause, "values" => $whereArray );
		}

		/**
		 *	Gets the mapped name of the last facet.
		 *
		 *	@param array $facets A list of the facets
		 *	@return The last facet's mapped name
		 */
		public function getLastFacetMappedName( $facets )
		{
			return $this->map[$facets[count($facets)-1]["facet"]];
		}

		/**
		 *	Searches the database (using RedBeanPHP) for a list of the next set
		 *	of facets and a count of how many items in the facet's result list.
		 *	This returns all the facets.
		 */
		public function searchNextFacetList( $facets )
		{
			// Get the last facet column name (the one for which we need the results)
			$lastFacet = $this->getLastFacetMappedName( $facets );
	
			// -------------------------------------------------------
			// We First do a search to calculate the number of values
			// for the facet at the last level selected
			// -------------------------------------------------------
			$sql = "SELECT `$lastFacet`, COUNT(`$lastFacet`) AS `countLastFacet` FROM `{$this->table}` WHERE ";

			$whereBits = $this->getSQLWhereClauseAndArray( $facets );
			$whereClause = $whereBits["statement"];
			$whereArray  = $whereBits["values"];
			
			// We group by the facet (to get counts)
			$sql .= "$whereClause GROUP BY `$lastFacet`";
	
			// if( strpos( $whereClause, "AND" ) !== false )
			// { var_dump( $facets ); var_dump( $sql ); var_dump( $whereArray ); }

			// Query the database for the facets
			$rows = R::getAll( $sql, $whereArray );

			// Woo, this is our results array which we'll return. We're doing something at last.
			$results = array( "facet"=>array(), "count"=>array() );
			foreach( $rows as $row )
			{
				$facet = $row[$lastFacet];
				$count = $row['countLastFacet'];
				
				$results["facet"][$facet] = $facet;
				$results["count"][$facet] = $count;
			}

			return $results;
		}

		/**
		 *	Returns the rows of the database that match the current selected facet set.
		 *	
		 *	@param array $facets The facet list (see getFacetList())
		 *	@param $start The result on which to start (for pagination)
		 *	@param $nResults The number of results to return
		 *	@return an array with key "results"
		 */
		public function getFacetResults( $facets, $start = 0, $nResults = 10 )
		{
			$results = array( "results" => array() );

			$whereBits = $this->getSQLWhereClauseAndArray( $facets );
			$whereClause = $whereBits["statement"];
			$whereArray  = $whereBits["values"];
			
			// -------------------------------------------------------------
			// Now we need to do a search to find the results at this level
			// -------------------------------------------------------------
			$sql = "SELECT * FROM `{$this->table}` WHERE $whereClause LIMIT $start,".($start+$nResults);
			
			// var_dump( $sql ); var_dump( $whereArray );
			
			// Query the database for the facets
			$rows = R::getAll( $sql, $whereArray );
	
			// Now put all the results from the database in the results array		
			foreach( $rows as $row )
			{	
				$a = array();
				foreach( $row as $k=>$v )
					if( !is_numeric( $k ) && isset( $this->invMap[$k] ) )
						$a[ $this->invMap[$k] ] = $v;

				if( !empty( $a ) )
					$results["results"][] = $a;
			}

			return $results;
		}

		/**
		 *	Loops through each of the facets in turn and calcuates the results.
		 *	$facets should be an array of arrays, where each inner array contains
		 *	"facet" property giving the name of the facet and "value" property
		 *	giving the value of the property (if there is one). The method
		 *	getFacetValues() can provide this from a $_GET array received from
		 *	the jquery facet browser.
		 *
		 *	@param $facets The facet and facet value array
		 *	@param $getResults
		 *	@param $start
		 *	@param nResults
		 */
		public function calculateFacet( $facets, $getResults, $start, $nResults )
		{
			// First search the facets in the next facet list.
			$results = $this->searchNextFacetList( $facets );
			
			// If we're getting values as well as facets
			if( $getResults )
				$results = array_merge( $results,
					$this->getFacetResults( 
						$facets, $start, $nResults ) );
	
			return $results;
		}
		
		/**
		 *	Returns a list of facets that are retrieved from
		 * 	the URL query variables. facets are numbers from 1
		 *	as in facetname1, facetvalue1, facetname2, facetvalue2, etc.
		 *	The method takes these values and names and puts them into
		 *	an object where property "facet" maps to the name, and property
		 *	"value" maps to the value.
		 *
		 *	e.g.
		 *		[facetname1=F1,facetvalue1=foo,facetname2=F2,facetvalue2=bar]
		 *	becomes
		 *		[ ["facet"=>"F1", "value"=>"foo"], ["facet"=>"F2", "value"=>"bar"] ]
		 *
		 *	@param queryVars The query variables in an array (e.g. $_GET)
		 */
		public function getFacetList( $queryVars )
		{
			$facets = array();
			$n = $queryVars["nFacets"];
			for( $i = 1; $i <= $n; $i++ )
			{
				$name = "";
				if( isset( $queryVars["facetname$i"] ) )
					$name = $queryVars["facetname$i"];
					
				$val = "";
				if( isset( $queryVars["facetvalue$i"] ) )
					$val = $queryVars["facetvalue$i"];
					
				$facets[] = array( "facet"=>$name, "value"=>$val );
			}
			return $facets;
		}	
	}
?>
