<?php
/**
 * CategoryGraph - A MediaWiki extension
 *
 *
 *
 * 
 * Copyright 2013 Daniel Renfro
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

// ------------ check if we are inside MediaWiki -----------------------

if ( !defined('MEDIAWIKI') ) {
    echo <<<EOT
To install my extension, you'll need to edit your LocalSettings.php with
the appropriate configuration directives. Please see the README that comes
with the software.
EOT;
    exit( 1 );
}

// ------------ includes (autoloaded) ---------------------------------

$includes = dirname(__FILE__) . '/';
$wgAutoloadClasses['CategoryGraph'] = $includes . 'CategoryGraph.body.php';
$wgAutoloadClasses['CategoryGraphApi'] = $includes . 'CategoryGraphApi.php';


// ------------- default global variables, constants, etc. ---------------------

define( 'CATEGORY_GRAPH_VERSION', '0.7 alpha' );

// ------------ credits ---------------------------------

$wgExtensionCredits['specialpage'][] = array(
	'name' 			=> 'CategoryGraph',
	'description' 	=> 'Makes a graph to display the heirarchy of categoies.',
	'author' 		=> 	'[mailto:bluecurio@gmail.com Daniel Renfro]',
    'version' 		=> CATEGORY_GRAPH_VERSION
);


// ------------ languages ---------------------------------

$wgExtensionMessagesFiles['CategoryGraph'] 	= dirname(__FILE__) . '/CategoryGraph.i18n.php';


// ------------ parser-tag initialization ---------------------------------

if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'wfCategoryGraphInitialization';
} else {
	$wgExtensionFunctions[] = 'wfCategoryGraphInitialization';
}

// ------------- API -----------------------------------------------------------

$wgAPIModules['categoryGraph'] = 'CategoryGraphApi';


// --------- ResourceLoader ----------------------------------------------

$wgResourceModules[ CategoryGraph::MODULE ] = array(
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
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'CategoryGraph',
);

// ------------ hooks ---------------------------------


$wgHooks['BeforePageDisplay'][] = function( OutputPage &$out, Skin &$skin ) {
	$out->addModules( CategoryGraph::MODULE );
	return true;
};


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


