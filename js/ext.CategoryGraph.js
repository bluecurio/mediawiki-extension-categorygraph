/*
    ext.CategoryGraph.js
    Copyright 2009-2010 Daniel Renfro, all rights reserved.
*/

$(document).ready( function() {
		
    /**
     * The server-side code for this extension adds some variables that might that were
     *   included in the parser-tag setup that might be useful. This code here uses those
     *   variables, but other instances don't necessarily need to.
     *
     *   wgCategoryGraph_Title
     *   wgCategoryGraph_MaxDescendants
     *   wgCategoryGraph_MaxAncestors
     */ 
        

	// auto-execute this function to fill the scoreboard with data.
	(function(){
	
        if ( mw.config.get('wgCategoryGraphOptions') === null ) {
            return;
        }

		var div = $( '#categoryGraph' );
		
		var fnAjaxFailed = function( node, data ) {
			if ( !data ) {
				data = {
					error: 'Loading the graph via ajax failed. Please contact an administrator.'
				};
			}		
			$(node)
                .removeClass( 'categoryGraphLoading' )
                .html( 'code:' + data.error.code + '<br />info: ' + data.error.info );
		}	

		$.ajax({
			beforeSend: function() {
				$(div)
                    .addClass( 'categoryGraphLoading' )
					.html( mw.msg( 'category-graph-loading-graph' ) );
			},
			url: mw.config.get( 'wgScriptPath' ) + '/api.php',
			type: 'POST',
			data: {
				'action': 'categoryGraph',
				'format': 'json',
				'title': mw.config.get( 'wgCategoryGraphOptions' ).title,
                'max_descendants': mw.config.get( 'wgCategoryGraphOptions' ).max_descendants,
                'max_ancestors': mw.config.get( 'wgCategoryGraphOptions' ).max_ancestors
			},
			success: function( data, textStatus, jqXHR ) {
				// sometimes we get a successful response, but it is filled with an error message
				if ( data.error ) {
					fnAjaxFailed( div, data );
				}
				else {
					$(div)
                        .removeClass( 'categoryGraphLoading' )
                        .html( data.result.html );
				}
			}, 
			error: function( jqXHR, textStatus, errorThrown ) {
				fnAjaxFailed( div );
			}, 
			timeout: 30000
		});
		
	})();
});
