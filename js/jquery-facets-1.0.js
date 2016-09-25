
/**
 *	A faceted search widget for jQuery UI.
 *
 *	This widget shows a faceted search browser that allows
 *	selection of the values within the facets and provides
 *	a means for calling to an external server to calculate
 *	the facet contents.
 *
 *	The widget generates AJAX calls with the following GET
 *	query variables:
 *
 *		nFacets - The total number of facets in the call
 *		facetname1  - The first facet
 *		facetvalue1 - The first facet value
 *		facetname2 ... etc. up to and including n
 *		all - Set to 'true' if you want results
 *
 *	The variable counters are 1-based.
 *
 *	@author David Dupplaw
 *	@created 7th April 2012
 */
var FacetedSearch =
{
	/**
	 *	Constructor
	 */
	_create : function()
	{
		// Set up the facet boxes
		var n = this.options.facets.length;
		var pc = 100.0/n;
		var facetList = $('<div class="facetList"></div>');
		for( i = 1; i <= n; i++ )
		{
			var d = $("<div class='facet facet_"+i+"'><div class='caption'>"+
				this.options.facets[i-1]+"</div></div>").
				width( pc+"%" );
								
			d.append( $("<div class='inner'></div>").
						attr("facet",i).
						height( this.options.facetHeight ) 
			);
				
			if( i == 1 )
				d.addClass( "first" );
			
			facetList.append( d );
		}
		
		var thisEl = this;
		facetList.sortable({
			axis: "x",
			revert: true,
			helper: "original",
			forcePlaceholderSize: true,
			placeholder: "facetDrag",
			handle: ".caption",
			start: function( event, ui )
			{
				ui.placeholder.css( "width", pc+"%" );
			},
			stop: function( event, ui )
			{
				thisEl.updateFacets( thisEl._updateFacetsFromElements( ui ) );
			}
		}).disableSelection();
		
		this.element.append( facetList );
		
		var r = $("<div class='results'><div class='inner'></div></div>");
		this.element.append( r );
			
		this.updateFacets( 1 );
	},
	
	/**
	 *	Updates the facet list based on the elements in the .facetList
	 *	div. It updates the facet array in the options and fixes some of
	 *	the classes in the facet divs as it does so.
	 *
	 *	@param ui The facet that was moved
	 */
	_updateFacetsFromElements : function( ui )
	{
		// Remove the facet_n classes
		for( i = 1; i <= this.options.facets.length; i++ )
			$(".facet").removeClass( "facet_"+i );
		
		this.options.facets = [];
		var thisEl = this;
		var fl = this.element.find(".facetList");
		var count = 1;
		var updateFacet = -1;
		fl.find( ".facet .caption" ).each( function()
		{
			thisEl.options.facets.push( $(this).text() );
			$(this).parent().addClass( "facet_"+count ).
				find( ".inner" ).attr( "facet", count );

			// If we found the moved element, then we chop the
			// selected element values back to the new position
			// of the facet (as the others are now meaningless
			// because they're out of sync with the facets)
			if( $(this).parent()[0] == ui.item[0] )
			{
				thisEl.options.selectedFacetValues = thisEl.options.selectedFacetValues.slice( 0, count );
				updatedFacet = count;
			}
			
			count++;
		} );
		
		$(".facet").removeClass("first");
		$(".facet").first().addClass("first");
		
		return updatedFacet;
	},
	
	/**
	 *	Returns an element that can be used as an element in a 
	 *	list within a facet view.
	 *
	 *	@param facet The index of the facet (0-based)
	 *	@param value The value of the element
	 *	@param count The number of results for the selected facet value
	 */
	_getFacetValueElement : function( facet, value, count )
	{
		var thisEl = this;
		var li = $("<li><a><span class='value'>"+value+" ("+count+")</span></a></li>").click( function()
		{
			// Update the values of the stored facet values
			thisEl.options.selectedFacetValues[facet] = value;
			thisEl.options.selectedFacetValues = thisEl.options.selectedFacetValues.slice( 0, facet+1 );
			
			// Update the facets to show the new values
			thisEl.updateFacets( facet+1 );
			
			// Remove any selections of facets, as we've just made a new one.
			$('.facet_'+facet+' .inner li').removeClass('selected');
			
			// Make this facet look selected.
			$(this).addClass( "selected" );
		} );
		
		if( this.options.selectedFacetValues[facet] == value )
			li.addClass( "selected" );
		
		return li;
	},
	
	/**
	 *	The default result printer is a standard result printer function
	 *	which plots the results as a table.
	 */
	_defaultResultPrinter: function( results )
	{
		// Clear the current results
		this.element.find(".results").empty();
		
		// Make an associative array of all the 
		// facets in all the results. We do this in
		// case all the results do not have all the facets.
		var facets = Array();
		for( r in results )
			for( f in results[r] )
				facets[f] = 1;
		
		// Set up the results table and its header
		var table = $("<table></table>").addClass( "defaultResultsPrinterTable" );
		var tableHeader = $("<tr></tr>");
		var pc = 100 / facets.length;
		for( f in facets )
			tableHeader.append( "<th>"+f+"</th>" ).width( pc + "%" );
		table.append( $("<thead></thead>").append( tableHeader ) );
		
		// Add all the results
		var l = "row_even";
		for( r in results )
		{
			// Alternate the class on each result
			if( l == "row_even" ) 
					l = "row_odd";
			else	l = "row_even";

			var resultElement = $("<tr></tr>").addClass( l );
			
			for( f in facets )
			{
				resultElement.append( "<td>"+results[r][f]+"</td>" );
			}

			table.append( resultElement );			
		}				
		
		this.element.find(".results").append( table );
	},
	
	/**
	 *	Updates the given facet with new information.
	 *
	 *	@param facet The facet to update
	 *	@param n The facet from which the update was fired
	 *	@param query The query parameters for this facet
	 *	@param allInfo Whether to return all results for this facet.
	 *	@param lastFacet Whether the facet is the last one in the list
	 */
	_updateFacetInfo: function( facet, n, query, allInfo, lastFacet )
	{
		var thisEl = this;
		var needsUpdating = false;

		if( facet >= n || lastFacet )		
		{
			// If allInfo is true, we'll add the "all" variable to the URL
			var a = "";
			if( allInfo || lastFacet )
				a = "&all=true";
			
			// Get the information for the given facet from the server	
			$.get( this.options.ajaxURL + query + a, function( data )
			{
				if( facet >= n )
				{
					// Put all the values for the given facet into the display
					var l = "row_even";
					var first = null;
					var count = 0;
					var el = null;
					for( d in data.facet )
					{
						if( first == null ) first = d;
						
						// Alternate the class on each result
						if( l == "row_even" ) 
								l = "row_odd";
						else	l = "row_even";
						
						// Get an element for the facet value
						thisEl.element.find( ".facet .inner[facet='"+facet+"']" ).append( 
							el = thisEl._getFacetValueElement( facet, data.facet[d], data.count[d] ).addClass(l) );
							
						count++;
					}
					
					// If there's only a single item in the facet list
					// and we're set to auto select singletons...
					if( count == 1 && thisEl.options.autoSelectSingletons )
					{
						thisEl.options.selectedFacetValues[facet] = data.facet[first];
						el.addClass( "selected" );
						needsUpdating = true;
					}
				}
				
				// If we've got all the info (which will be for the most specific facet)
				// we'll update the results panel with the results returned
				l = "row_even";
				if( allInfo || lastFacet )
					if( thisEl.options.resultPrinter == null )
							thisEl._defaultResultPrinter( data.results );
					else	thisEl.options.resultPrinter( data.results );
				
				if( needsUpdating )
					thisEl.updateFacets( n+1 );
			}, "json" );
		}		
	},
	
	/**
	 *	Update the values of the objects in the facets.
	 *
	 *	@param n The facet to start updating from (1-based)
	 */
	updateFacets : function( n )
	{
		// Clear the facets first
		this.clearFacets( n );
		
		var queryVar = "?";
		var stopNow = false;
		var value = undefined;
		
		// Loop through all the facets and update the views
		for( facet = 1; facet <= this.options.facets.length; facet++ )
		{
			queryVar += "facetname"+facet+"="+this.options.facets[facet-1]+"&";
			
			// If there's a value it must not be the last facet in the list
			// so there should be a value associated with it which we need to
			// pass to the service
			value = this.options.selectedFacetValues[facet];
			if( value !== undefined )
				queryVar += "facetvalue"+facet+"="+value+"&";
			else
				// If there's no value defined it must be the last facet
				stopNow = true;
				
			// We need to add the number of facets to resolve to the service call.
			var q = queryVar + "nFacets="+facet;
			
			// Get the facet information
			this._updateFacetInfo( facet, n, q, stopNow, facet == this.options.facets.length );
			
			if( stopNow )
				break;
		}
	},
	
	/**
	 *	Removes all the display of the facets. Does not reset any
	 *	of the facet values or facets. The parameter determines
	 *	which facets are untouched.
	 *
	 *	@param n The facet from which to clear values (1-based)
	 */
	clearFacets : function( n )
	{
		this.element.find('.facetList .facet .inner').each( function()
		{
			var id = $(this).attr("facet");
			if( id >= n )
				$(this).empty();
		} );
	}
}

jQuery.widget( "ui.facets", FacetedSearch );

$.ui.facets.prototype.options =
{
	// The URL at which the data server is listening
	ajaxURL : "",
	
	// The names of the facets to display
	facets : [],
	
	// The selected values in the facets (in order)
	selectedFacetValues : [],
	
	// The height of the facet browser
	facetHeight : 250,
	
	// The user-defined function to print each result
	resultPrinter: null,
	
	// Whether to auto-select the value from facets with only one value
	autoSelectSingletons: true,
}