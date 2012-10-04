<?php

/** 
 * $Id$
 * @lastmodified $LastChangedDate$
 * @filesource $URL$
 */

function fnParseParameters($noopt = array()) {
	$result = array();
	$params = $GLOBALS['argv'];
	// could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
	reset($params);
	while (list($tmp, $p) = each($params)) {
		if ($p{0} == '-') {
			$pname = substr($p, 1);
			$value = true;
			if ($pname{0} == '-') {
				// long-opt (--<param>)
				$pname = substr($pname, 1);
				if (strpos($p, '=') !== false) {
					// value specified inline (--<param>=<value>)
					list($pname, $value) = explode('=', substr($p, 2), 2);
				}
			}
			// check if next parameter is a descriptor or a value
			$nextparm = current($params);
			if ( !in_array($pname, $noopt)
				  && $value === true 
				  && $nextparm !== false 
				  && $nextparm{0} != '-'
			) {
				list($tmp, $value) = each($params);
			}
			$result[$pname] = $value;
		} else {
			// param doesn't belong to any option
			$result[] = $p;
		}
	}
	return $result;
}


function help() {
	echo <<<USAGE
USAGE: 
  php5 {$_SERVER['SCRIPT_NAME']} [ options ] [ input file ]

    --wiki      -w      Path to the Mediawiki installation.
    --verbose   -v      Print better messages.

USAGE;
	exit;
}

$params = fnParseParameters( $GLOBALS['argv'] );

if ( isset($params['w']) || isSet($params['wiki']) ){
	$wikipath = ( isSet($params['w']) ) 
		? $params['w']
		: $params['wiki'];
} else {
	help();
	exit();
}
require_once( $wikipath . "/maintenance/commandLine.inc" );

require_once( '/usr/local/wiki-extensions/trunk/CategoryGraph/CategoryGraph.php' );
$wgGraphVizInstallPath = '/opt/local/bin/';
//$wgCategoryGraph_Debug = true;


if ( isSet($params['v']) || isSet($params['verbose']) ) {
	$wgVerbose = true;
}

$graph = new CategoryGraph( 
	Title::newFromText( 'CACAO Fall 2011', NS_CATEGORY )
);

print $graph->render();
print $graph->getDot();
