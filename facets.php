<?php
	/**
	 *	Demonstration AJAX service for accessing facets
	 *	about car emissions.
	 *
	 *	Allowable $_GET parameters:
	 *		all: If 'true', will get th
	 *		number:
	 *		start:
	 *	plus the usual facet stuff (see the facet widget page
	 *	for more information: http://david.dupplaw.me.uk/developer/jquery-facets)
	 *
	 *	@author David Dupplaw <david@dupplaw.me.uk>
	 *	@created 22nd November 2013
 	 */

	// Load the composer dependencies (includes RedBeanPHP)
	require_once "vendor/autoload.php";

	use RedBean_Facade as R;

	// -------------------------------------
	// Set up the table name here
	// -------------------------------------
	$dbTable = "carinfo";

	// -------------------------------------
	// To connect to a MySQL database
	// -------------------------------------
	// $dbHost = "my.database.example.com";
	// $dbDatabase = "my.database";
	// $dbUsername = "my.username";
	// $dbPassword = "my.password";
	// R::setup('mysql:host='.$dbHost.';dbname='.$dbDatabase,$dbUsername,$dbPassword);
	// --------------------------------------

	// We're using a local sqlite database
	$sqlite = "sqlite:".__DIR__."/CarInfo.sqlite";
	R::setup( $sqlite, null, null );

	// Include the facet helper
	require_once "FacetHelper.php";

	// ------------------------------------------------------------------------------------- //
	
	// Create a map that maps the facet name to the column name
	$table = $dbTable;
	$map = array(
		"Manufacturer" => "Vehicle Manufacturer Name",
		"Model" => "Represented Test Veh Model",
		"MPG" => "FE Bag 1",
		"CO2" => "CO2 (g/mi)",
		"CO" => "CO (g/mi)",
		"NOx" => "NOx (g/mi)",
		"Year" => "Model Year",
		"Type" => "Vehicle Type",
		"Horsepower" => "Rated Horsepower",
		"Cylinders" => "# of Cylinders and Rotors",
		"Transmission" => "Tested Transmission Type",
		"Gears" => "# of Gears",
		"Drive" => "Drive System Description",
		"Weight" => "Equivalent Test Weight (lbs.)",
	);

	// Instantiate a new facet helper pointing to the table of our car information
	$facetHelper = new FacetHelper( $table, $map );

	$all = false;
	if( isset( $_GET["all"] ) && $_GET["all"] == "true" )
		$all = true;
		
	$start = 0;
	$number = 20;
	
	if( isset( $_GET["start"] ) )
		$start = $_GET["start"];
		
	if( isset( $_GET["number"] ) )
		$number = $_GET["number"];
	
	
	try
	{
		$r = $facetHelper->calculateFacet( 
			$facetHelper->getFacetList($_GET), 
			$all, $start, $number );
	}
	catch( Exception $e )
	{
		$r = array();
		$r["error"] = $e->getMessage();
		$r["success"] = false;
		echo $e;
	}
	
	header( "Content-type: application/json" );
	print json_encode( $r );
	die();
?>
