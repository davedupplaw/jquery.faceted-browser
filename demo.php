<?php
	// Demo for the faceted browser
	// By David Dupplaw
	// 7th April 2012
	
	$data = array(
		array( "id"=>1, "Make"=>"Ford", "Model" => "Focus", "Colour"=>"Red", "Year"=>2002 ),
		array( "id"=>2, "Make"=>"Ford", "Model" => "Focus", "Colour"=>"Blue", "Year"=>2002 ),
		array( "id"=>3, "Make"=>"Ford", "Model" => "Focus", "Colour"=>"Grey", "Year"=>2001 ),
		array( "id"=>4, "Make"=>"Vauxhall", "Model" => "Agile", "Colour"=>"Red", "Year"=>2006 ),
		array( "id"=>5, "Make"=>"Vauxhall", "Model" => "Omega", "Colour"=>"White", "Year"=>2001 ),
		array( "id"=>6, "Make"=>"Toyota", "Model" => "Aygo", "Colour"=>"Blue", "Year"=>2008 ),
		array( "id"=>7, "Make"=>"Toyota", "Model" => "Aygo", "Colour"=>"Grey", "Year"=>2006 ),
		array( "id"=>8, "Make"=>"Toyota", "Model" => "Aygo", "Colour"=>"Grey", "Year"=>2006 ),
		array( "id"=>9, "Make"=>"Toyota", "Model" => "Auris", "Colour"=>"Red", "Year"=>2009 ),
		array( "id"=>10, "Make"=>"Toyota", "Model" => "Auris", "Colour"=>"Grey", "Year"=>2009 ),
		array( "id"=>11, "Make"=>"Toyota", "Model" => "Auris", "Colour"=>"Green", "Year"=>2007 ),
		array( "id"=>12, "Make"=>"Toyota", "Model" => "Auris", "Colour"=>"Grey", "Year"=>2009 ),
	);
	
	function getMatches( $data, $facet, $value, $getResults, $start, &$nResults )
	{
		$results = array( "results" => array(), "facet"=>array(), "count"=>array() );
		foreach( $data as $a )
		{
			// Add just the matching values
			if( (isset( $a[$facet] ) && $a[$facet] == $value) || $value == "" ) 
			{
				// If we should return the results too, we'll add them here.
				if( $getResults || $value != "" )
					$results["results"][] = $a; 
				
				$results["facet"][$a[$facet]] = $a[$facet];
				if( !isset( $results["count"][$a[$facet]] ) )
						$results["count"][$a[$facet]] = 1;
				else	$results["count"][$a[$facet]]++;
			}
		}
		
		return $results;
	}
	
	function calculateFacet( $data, $facets, $getResults, $start, $nResults )
	{
		$d["results"] = $data;
		
		// Loop through the facets in order
		foreach( $facets as $facet )
		{
			$d = getMatches( $d["results"], $facet["facet"], $facet["value"], $getResults, $start, $nResults );
//			print "::: For facet ".$facet["facet"]."=".$facet["value"]."\n"; print_r( $d );
		}
		
		return $d;
	}
	
	function getFacetList( $queryVars )
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
	
	$all = false;
	if( isset( $_GET["all"] ) && $_GET["all"] == "true" )
		$all = true;
		
	$start = 0;
	$number = 20;
	
	if( isset( $_GET["start"] ) )
		$start = $_GET["start"];
		
	if( isset( $_GET["number"] ) )
		$number = $_GET["number"];
	
	header( "Content-type: application/json" );
	print json_encode( calculateFacet( $data, getFacetList($_GET), $all, $start, $number ) );
?>