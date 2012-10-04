<?php

// ============ check if we are inside MediaWiki =======================
if ( !defined('MEDIAWIKI') ) {
    echo <<<EOT
To install my extension, you'll need to edit your LocalSettings.php with
the appropriate configuration directives. Please see the README that comes
with the software. 
EOT;
    exit( 1 );
}

// ============ includes (autoloaded) =================================
$includes = dirname(__FILE__) . '/';
$wgAutoloadClasses['CategoryGraph'] = $includes . 'CategoryGraph.body.php';
$wgAutoloadClasses['CategoryGraphApi'] = $includes . 'CategoryGraphApi.php';


// ============= default global variables, constants, etc. =====================
define( 'CATEGORY_GRAPH_VERSION', '0.7 alpha' );
define( 'CATEGORY_GRAPH_MODULE', 'CategoryGraph' );

/**
 * The resources used by MediaWiki's ResourceLoader.
 **/
$wgCategoryGraph_Modules = array(
	CATEGORY_GRAPH_MODULE => array(
		'scripts' =>  array(
			'js/ext.CategoryGraph.js'
		),
		'styles' => array(
			'css/ext.CategoryGraph.css'
		),
        'dependencies' => array(
            'mediawiki.language'
        ),
        'messages' => array(
            'category-graph-loading-graph'
        )   
	)
);

// ============ credits =================================
$wgExtensionCredits['specialpage'][] = array(
	'name' 			=> 'CategoryGraph',
	'description' 	=> 'Makes a graph to display the heirarchy of categoies.',
	'author' 		=> 	'[mailto:bluecurio@gmail.com Daniel Renfro]',
    'version' 		=> CATEGORY_GRAPH_VERSION
);

// ============ hooks =================================
$wgHooks['ResourceLoaderRegisterModules'][] = 'efCategoryGraph_RegisterModules';
$wgHooks['BeforePageDisplay'][] = 'efCategoryGraph_BeforePageDisplay';


// ============ languages =================================
$wgExtensionMessagesFiles['CategoryGraph'] 	= dirname(__FILE__) . '/CategoryGraph.i18n.php';


// ============ parser-tag initialization =================================
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'wfCategoryGraphInitialization';
} else {
	$wgExtensionFunctions[] = 'wfCategoryGraphInitialization';
}

// ============= API ===========================================================
$wgAPIModules['categoryGraph'] = 'CategoryGraphApi';


// ============ global-level functions =================================


/**
 * Register the parser hooks
 */
function wfCategoryGraphInitialization( &$parser ) {
	$parser->setHook( 'categoryGraph', array( new CategoryGraph, 'execute') );
	return true;
}

/**
 * Load the resources.
 **/
function efCategoryGraph_RegisterModules( $resourceLoader ) {
	global $wgExtensionAssetsPath, $wgCategoryGraph_Modules;
		
	$localpath = dirname( __FILE__ ) . '/';
	$remotepath = "$wgExtensionAssetsPath/CategoryGraph/";

	foreach ( $wgCategoryGraph_Modules as $name => $resources ) {
		$resourceLoader->register( 
			$name, new ResourceLoaderFileModule( $resources, $localpath, $remotepath )
		);
	}
	return true;
}


/**
 * Include the necessary CSS and JS.
 *
 * If the Resource Loader is not working as reliably as expected, or is not loaded at all,
 *   this will shove the CSS and Javascript into the <head> of the page using this code. 
 *   But beware, the Javascript will probably not work as expected (or at all) because jQuery 
 *   hasn't been loaded yet.
 *
 */
function efCategoryGraph_BeforePageDisplay( $out ) {
	global 	$wgExtensionAssetsPath, $wgCategoryGraph_Modules, $wgResourceModules;

	if ( isSet($wgResourceModules) ) {	
		$out->addModules( CATEGORY_GRAPH_MODULE );
	}
	else {
		foreach ( $wgCategoryGraph_Modules as $moduleName => $module ) {
			foreach ( $module as $type => $array ) {
				switch ( $type ) {
					case "scripts":
						foreach ( $array as $js ) {
							$out->addScriptFile( $wgExtensionAssetsPath . '/CategoryGraph/'. $js );
						}
						break;
					case "styles": 
						foreach ( $array as $css ) {
							$out->addExtensionStyle( $wgExtensionAssetsPath . '/CategoryGraph/' . $css );
						}
						break;						
				}
			}
		}
	}
	return true;
}

