<?php

class CategoryGraph {

	const MODULE = 'ext.categoryGraph';

    // --------------- Member variables --------------------------------------------------

    /**
     * These are the parameters that are set in the parser-tag in the wikipage, not
     *    the parameters that were given to the ajax call. See the API module for those.
     */
    protected $params;

    /**
     * Parameters for the parser-tag that are recognized and do something.
     */
    protected $acceptedParameters = array(
        'title',            // the category to use to construct the graph
        'max_descendants',  // the max number of descendant nodes to show in the graph
        'max_ancestors',     // the max number of ancestor nodes to show in the graph
        'format'
    );

    /**
     * This keeps track of how many levels down we have recursed when finding descendants.
     *   Without this variable we are lost.
     */
    protected $subcat_recusion_level = 0;
    protected $ancestor_recursion_level = 0;

	/**
	 * To check for circular references. This is not only to keep MW sane (and from recursing
	 *    infinitely,) but also for Graphviz (which doesn't handle cyclic graphs.)
	 */
	protected $descendents_encountered = array();
	protected $ancestors_encountered = array();

    /**
     * A place to hold our generated dot-file.
     **/
    protected $dot;

	/**
	 * The title of the Category page that we're making a graph for...as a parameter
	 *   for the constructor.
	 */
    protected $title;

    /**
     * A list of formats supported by the dot program. BE WARNED: not all of these are
     *    suitable for web-display. Maybe in the future we will have a "download as..."
     *    and we can use this array for something other than validation.
     *
     */
    protected $acceptableFormats = array(
        'canon',
        'dot',
        'fig',
        'gd',
        'gif',
        'hpgl',
        'imap',
        'cmap',
        'jpg',
        'mif',
        'mp',
        'pcl',
        'pic',
        'plain',
        'plain-ext',
        'png',
        'ps',
        'ps2',
        'svg',
        'vrml',
        'vtx',
        'wbmp'
    );

    /**
     * The path to GraphViz.
     **/
    protected $exec;

    /**
     * The default attributes of the overall graph.
     **/
    protected $graphAttributes;

     /**
      * The default attributes to use to create all the nodes.
      **/
    protected $nodeAttributes;

    /**
     * The format of the output. If you are going to display this on the web you'll probably want
     *    to read the GraphViz documentation and pick a standard format like 'gif', 'jpg', 'svg', etc.
     **/
    protected $format;

    // --------------- Methods --------------------------------------------------


	/**
	 *
	 */
    public function __construct( Title $t = null ) {

		if ( !is_null($t) )  {
            $this->setTitle( $t );
		}


        // default attributes for the graph
        $this->setGraphAttributes(
            array(
                'nodesep' => '0.2',             // min distance (inches) between two adjacent nodes in same rank
                'rankseq' => '0.2 equally',     // min distance between two adjacent ranks
                'rankdir' => 'TB',              // draw Top to Bottom
                //'ratio' => '1.2',             // this can have MAJOR consequences on rendering time
                //'size' => '4,4',                // in inches (keep in mind resolution (~72dpi?))
            )
        );

        /**
         * Default attributes to set for all nodes.
         **/
        $this->setNodeAttributes(
            array(
                'color' => 'black',
                'shape' => 'note',
                'fontname' => 'Helvetica',
                'fontsize' => '10.0',
            )
        );

        // set the default format
        $this->setParameter( 'format', 'jpg' );
        $this->setParameter( 'max_descendants', -1 );
        $this->setParameter( 'max_ancestors', -1 );
    }

    /**
     * Sets the title to create a graph for.
     **/
    public function setTitle( Title $t ) {
        $this->title = $t;
        return true;
    }

	/**
	 *
	 */
	public function getAcceptedParameters() {
		return $this->acceptedParameters;
	}

	/**
	 * Getter for the params. Parameter keys are always LOWERCASE.
	 *
	 */
	public function getParameter( $parameter ) {
		if ( in_array($parameter, array_keys($this->params)) ) {
			return $this->params[$parameter];
		}
		return false;
	}

    public function setParameter( $parameter, $value ) {
        $this->params[ $parameter ] = $value;
        return true;
    }


    public function getGraphAttributes() {
        return $this->graphAttributes;
    }

    public function setGraphAttributes( array $attributes ) {
        $this->graphAttributes = $attributes;
        return true;
    }

    public function getNodeAttributes() {
        return $this->nodeAttributes;
    }

    public function setNodeAttributes( array $attributes ) {
        $this->nodeAttributes = $attributes;
        return true;
    }

    public function getExecPath() {
        return $this->exec;
    }

    public function getDot() {
        return $this->dot;
    }

    /**
     * You can set this variable or use the global variable $wgGraphVizInstallPath.
     **/
    public function setExecPath( $path_to_graphviz_install ) {
        if ( substr($path_to_graphviz_install, -1, 1) != "/") {
            $path_to_graphviz_install .= '/';
        }

        if ( !is_dir($path_to_graphviz_install) ) {
            $error = <<<EOT
The path to the graphviz program ($path_to_graphviz_install) is not a valid directory.
You can set this variable by calling the __METHOD__ method or by setting the global
variable \$wgGraphVizInstallPath in LocalSettings.php.
EOT;

            throw new MWException( $error );

        }

        if ( !is_executable($path_to_graphviz_install . 'dot') ) {
            $error = <<<EOT
The CategoryGraph extension could not find the Graphviz 'dot' program in the path "$path_to_graphviz_install".
Please make sure GraphViz is installed and working.
EOT;
            throw new MWException( $error );
        }

        $this->exec = $path_to_graphviz_install;
        return true;
    }


    protected function checkGlobalVariables() {
        global $wgCategoryGraph_MaxDescendants,
            $wgCategoryGraph_MaxAncestors,
            $wgCategoryGraph_Format,
            $wgCategoryGraph_NodeAttributes,
            $wgCategoryGraph_GraphAttributes,
            $wgGraphVizInstallPath;


        // grab global settings. default settings are setup in the constructor
        if ( isSet($wgCategoryGraph_MaxDescendants) ) {
            $this->setParameter( 'max_descendants', $wgCategoryGraph_MaxDescendants );
        }
        if ( isSet($wgCategoryGraph_MaxAncestors) ) {
            $this->setParameter( 'max_ancestors', $wgCategoryGraph_MaxAncestors );
        }
        if ( isSet($wgCategoryGraph_Format) ) {
            $this->setParamter( 'format', $wgCategoryGraph_Format );
        }
        if ( isSet($wgCategoryGraph_NodeAttributes) ) {
            $this->setNodeAttributes( $wgCategoryGraph_NodeAttributes );
        }
        if ( isSet($wgCategoryGraph_GraphAttributes) ) {
            $this->setGraphAttributes( $wgCategoryGraph_GraphAttributes );
        }


        // the path where graphviz put it's binaries...specifically the 'dot' program
        if ( !isSet($wgGraphVizInstallPath) ) {
            if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'Darwin' ) ) {
                // '/' will be converted to '\\' later on, so feel free how to write your path C:/ or C:\\
                $this->setExecPath( 'C:/Program Files/Graphviz/bin/' );
            } else {
                //  common: '/usr/bin/'  '/usr/local/bin/' or (if set) '$DOT_PATH/'
                $this->setExecPath( '/usr/bin/' );
            }
        }
        else {
            $this->setExecPath( $wgGraphVizInstallPath );
        }
    }

	/**
	 * Here we setup the page for loading with ajax later
	 */
	public function execute( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgTitle, $wgOut, $wgCommandLineMode, $wgUser;

		if ( $wgCommandLineMode ) {
			return "";
		}

		// turn off caching on pages with this tag
		$parser->disableCache();

        // check if we've got global variables set that need to override our default settings
        $this->checkGlobalVariables();

        // parse the input and see what we got...
        // and, make sure what we get is already expanded
        $localParser = new Parser();
		$input = $localParser->getPreloadText(
			$input,
			$wgTitle,
			ParserOptions::newFromUser( $wgUser )
        );

        // Loop through lines of input, overwrite any default variables set by this object or by a global variable with
        //   the values set in the parser-tag.
        foreach ( explode("\n", $input) as $line ) {

			// skip blank lines
			if ( trim($line) == "" ) {
				continue;
			}
			// skip comments
			if ( strpos($line, '#') !== false ) {
				if ( preg_match('/^#/', trim($line) ) ) { continue;	}
				$line = preg_replace( '/#.*$/', "", $line );
			}
			// parse out the options
			if ( preg_match('/^(.*?)\s*=\s*(.*)/', $line, $matches) ) {
				$parameter = $matches[1];
				$value = $matches[2];
				if ( in_array($parameter, $this->getAcceptedParameters()) ) {
					$this->params[ strtolower($parameter) ] = $value;
				}
			}
		}

        // set the default parameters
        if ( !isSet($this->params['title']) ) {
            // make sure that this is a category page so we can use it's title
            //    as the default value.
            global $wgTitle;
            if ( $wgTitle->getNamespace() != 14 ) {
                return Xml::tags(
                    'span',
                    array( 'class' => 'error' ),
                    wfMsg( 'category-graph-no-title' )
                );
            }
            $this->params['title'] = $wgTitle->getFullText();
        }
        if ( !isSet($this->params['max_descendants']) ) {
            $this->params['max_descendants'] = -1; // int, not string
        }
        if ( !isSet($this->params['max_ancestors']) ) {
            $this->params['max_ancestors'] = -1; // int, not string
        }

        // make an array of variables that we want to hand off to the JavaScript
        $options = new StdClass;
        $options->{'title'} = $this->params['title'];
        $options->{'max_descendants'} = $this->params['max_descendants'];
        $options->{'max_ancestors'} = $this->params['max_ancestors'];
        $options->{'format'} = $this->params['format'];

		// Put those variables into the page as a JavaScript object.
		// You you access these as globals or with mw.config.get( 'wgCategoryGraphOptions' )
		$wgOut->addScript(
			Skin::makeVariablesScript( array('wgCategoryGraphOptions' => $options ) )
		);

		// have to explicity open, then close the DIV
		$html = "";
		$html .= Xml::openElement(
			'div',
			array(
				'id' => 'categoryGraph'
			)
		);
		$html .= Xml::closeElement( 'div' );
		return $html;
	}

    public function render() {
        return $this->ajaxResponse();
    }

	/**
	 * This is where we actually draw the graph
	 */
	public function ajaxResponse() {

        $this->checkGlobalVariables();

        // format the name of the file, append the extension
        $this->filename = $this->appendFileExtension(
            $this->getFilename(
                $this->title,
    	    	$this->params['max_descendants'],
	         	$this->params['max_ancestors']
              )
        );
        $this->path_to_file = $this->getPathToFile( $this->filename );
        // create the dot specification for the graph
		$this->dot = $this->createDot(
			$this->getDescendants( $this->title ),
			$this->getAncestors( $this->title )
        );

        // turn the dot spec into an image/whatever
	    $retval = $this->makeGraph(
			$this->dot,
			$this->path_to_file
        );
        if ( $retval === false ) {
            return $this->errorBox( wfMsg('category-graph-error') );
        }

        // return the image wrapped in an <img> tag, possibly other stuff
        return $this->getImageHTML();
    }

    /**
     * This should return a ui-state-error or ui-state-highlight box from the jQuery ui
     *
     **/
    protected function errorBox( $message ) {
        return $message;
    }

    /**
     * TODO: make this NOT A HARDCODED PATH!
     **/
    public function getImageHTML() {
        global $wgCategoryGraph_Debug, $wgExtensionAssetsPath;

        $html = "";
        $html .= sprintf(
            '<img src="%s/CategoryGraph/cache/%s">',
            $wgExtensionAssetsPath,
            $this->filename
        );
        if ( $wgCategoryGraph_Debug ) {
            $html .= '<pre>' . htmlentities($this->dot) . '</pre>';
        }
        return $html;
    }

    /**
     * TODO: implement a better caching scheme.
     **/
    public function getPathToFile( $filename ) {
        return dirname( __FILE__) . '/cache/' . $filename;
    }

    public function addAjaxParams( $params ) {
        // don't overwrite everything, just the things we got from $params
        foreach ( $params as $key => $value ) {
            $this->params[$key] = $value;
        }
        return true;
    }

    /**
     * Creates the filename *without* the file-extension (which depends on the value in
     *    the global variable $wgCategoryGraph_Format.)
     *
     * TODO: implement a better naming scheme. maybe md5() or sha1() ?
     */
	protected function getFilename( Title $t, $maxDesc, $maxAnc ) {
		$filename = "";
		$filename = $t->getDBkey();
		if ( $maxDesc && $maxDesc != -1 ) {
			$filename .= '_d' . $maxDesc;
		}
		if ( $maxAnc && $maxAnc != -1 ) {
			$filename .= '_a' . $maxAnc;
		}
		return $filename;
	}

    protected function appendFileExtension( $filename ) {
        return $filename . '.' . $this->params['format'];
    }

	/**
	 * The system-call to create the image.
	 */
	protected function makeGraph( $dot, $path_to_file ) {

        // got to have something to make!
		if ( !$dot || !is_string($dot) ) {
			return false;
        }

        // we should have checked this already, but check it again.
        if ( !in_array($this->params['format'], $this->acceptableFormats) ) {
            return false;
        }

        // run the dot program
		$command = sprintf(
			'%sdot -T%s',
            $this->exec,
            $this->params['format']
        );

        // options for the process-handling
		$descriptorspec = array(
			0 => array( 'pipe', 'r' ),
			1 => array( 'file', $path_to_file, 'w' ),
			2 => array( 'pipe', 'w' )
		);

        // open the process
        $process = proc_open( $command, $descriptorspec, $pipes );

        // read/write to/from the open process
		if ( is_resource($process) ) {
			fwrite( $pipes[0], $dot );
            fclose( $pipes[0] );
            // everything is done, return
			return proc_close( $process );
		}
		return false;
	}


	/**
	 * Title::getText isn't smart enough to give us what we want; it cuts off
	 *   everything before a colon, whether or not it is a valid namespace prefix
	 *   for this language.
	 *
	 */
	protected function getText( Title $t ) {
		global $wgContLang;

        $namespace = $wgContLang->getNSText( NS_CATEGORY );
        if ( preg_match('/^'.$namespace.'/', $t->getPrefixedDBkey()) ) {
            $key = preg_replace( '/^'.$namespace.':/', "", $t->getPrefixedDBkey() );
        }
        else {
            $key = $t->getPrefixedDBkey();
        }
        return $key;
	}


	/**
	 * Gets all the descendants recursively. Ignores redirects to categories, and probably
	 *    recurses infinity when encountering a circular graph.
	 *
	 */
    public function getDescendants( Title $t, $level = 1 ) {

        // to hold our descendants
        $stack = array();

		$key = $this->getText( $t );

		if ( in_array( $key, $this->descendents_encountered ) ) {
			return false;
		}
		array_push( $this->descendents_encountered, $key );

        // get a handle
        $dbr =& wfGetDB( DB_SLAVE );

        // query for all the subcategories
		$res = $dbr->select(
			array('page', 'categorylinks'),
			'page_title',
			array(
				'cl_to = "'.$key.'"',
				'page_namespace = ' . NS_CATEGORY,
				'page_is_redirect = 0',
			),
            __METHOD__,
            array(
            	'ORDER BY' => 'page_title ASC'
            ),
            array(
                'page' => array( 'INNER JOIN', 'cl_from = page_id' )
            )
        );

        // return early if we can
        if ( $dbr->numRows($res) == 0 ) {
            return array();
        }

        // get all the subcategories
		while ($row = $dbr->fetchRow($res)) {
			$resultKey = $this->getText(
				Title::newFromText( $row['page_title'], NS_CATEGORY )
			);
            $stack[ $resultKey ] = str_replace( " " , '_', $t->getFullText() );
        }

        // keep track of how many levels down we have been
        if ( $this->subcat_recusion_level < $level) {
            $this->subcat_recusion_level = $level;
        }

        // should we keep recursing?
        $level++;
        if ( $this->getParameter('max_descendants') != -1 && $level > $this->getParameter('max_descendants') ) {
            return $stack;
        }

        // recurse
        foreach ( $stack as $parent => $current ) {
            $nt = Title::newFromText( $parent, NS_CATEGORY );
            $d = $this->getDescendants( $nt, $level );
            if ( $d === false ) {
            	return $stack;
            }
            elseif ( $d ) {
                $stack[$parent] = $d;
            }
        }

        // finally, back out.
        return $stack;
    }


	public function getImmediateParentCategories( Title $t = null ) {
		global $wgContLang;

		if ( is_null($t) ) {
			$t = $this->title;
		}

		$titleKey = $this->getText( $t );
        $dbr =& wfGetDB( DB_SLAVE );

		if ( in_array( $titleKey, $this->ancestors_encountered ) ) {
			return array();
		}
		array_push( $this->ancestors_encountered, $titleKey );

		$result = $dbr->select(
			array('page', 'categorylinks'),
			'cl_to',
			array(
				'page_title = "'.$titleKey.'"',
				'page_namespace = ' . NS_CATEGORY,
				'page_is_redirect = 0',
				'cl_from <> 0'
			),
            __METHOD__,
            array(
            	'ORDER BY' => 'cl_sortkey'
            ),
            array(
                'page' => array( 'INNER JOIN', 'cl_from = page_id' )
            )
        );

        $parentCategories = array();
        if ( $dbr->numRows($result) > 0 ) {
        	while ( $row = $dbr->fetchObject($result) ) {
        		$parentCategories[ $wgContLang->getNSText( NS_CATEGORY ) . ':' . $row->cl_to ] = $t->getFullText();
        	}
        }
        return $parentCategories;
	}



	public function getAncestors( Title $t, $level = 1 ){
		global $wgContLang;

        $stack = array();

        // keep track of how many levels up we have been
        if ( $this->ancestor_recursion_level < $level ) {
            $this->ancestor_recursion_level = $level;
        }

        // should we keep recursing? (or just cursing? #$#$%@!!!)
        $level++;
        if ($this->getParameter('max_ancestors') != -1 && $level > $this->getParameter('max_ancestors') ) {
            return $stack;
        }

		$parents = $this->getImmediateParentCategories( $t );

		foreach ( $parents as $parent => $current ) {
			$nt = Title::newFromText( $parent, NS_CATEGORY );
			if ( $nt ) {
				$stack[$parent] = $this->getAncestors( $nt, $level );
			}
		}

        // finally, back out.
        return $stack;
    }

	public function createDot( array $descendants = array(), array $ancestors = array() ) {

		$dot = "";
		// open the graph and assign graph-attributes
		$dot .= "strict digraph G {\n";
		foreach ( $this->graphAttributes as $key => $value ) {
			$dot .= sprintf( "\t\"%s\"=\"%s\";\n", $key, $value );
        }

        $dot .= "\tcomment=\"". str_replace('"', '\"', $this->getGraphComment()) ."\"\n";

        // set the attributes for all nodes
        $dot .= "\tnode " . $this->makeNodeAttributes( $this->nodeAttributes ) . "\n";

        // set the attributes for all edges
        // TODO

		// attributes for the main node
		$dot .= "\t\"" . $this->title->getFullText() . "\";\n";

		// ancestors, rooted with our title
		$dot .= $this->array2dot4ancestors( array( $this->title->getFullText() => $ancestors) );

		// descendants
		$dot .= $this->array2dot4descendants( array( $this->title->getFullText() => $descendants) );

		$dot .= "}";

		return $dot;
	}

    public function getGraphComment() {
        global $wgSitename;

        if ( !isSet($this->title) ) {
            return "";
        }
        $comment = "";
        $article = new Article( $this->title );
        $comment = 'Graph of the page ' .$article->getTitle()->getFullText() . ' from '.$wgSitename.'. Created on ' . date('c', time());

        return $comment;
    }



	protected function makeNodeAttributes( array $attributes ) {
		$a = array();
		foreach ( $attributes as $key => $value ) {
			array_push( $a, $key . '=' . $value );
		}
		return '[' . implode( ',', $a ) .']';
	}


	protected function array2dot4descendants( $array = array(), $ancestor = "" ) {

		$dot = "";
		foreach ( $array as $current => $child ) {
            $current = str_replace( '_', ' ', $current );
            //$dot .= "\"$current\" [label=< <table  border=\"0\" cellborder=\"1\" cellspacing=\"0\"><tr><td>$current</td></tr></table> >];";
			if ( is_array( $child ) ) {
                if ( $ancestor ) {
                    $ancestor = str_replace( '_', ' ', $ancestor );
                    //$dot .= "\"$ancestor\" [label=< <table border=\"0\" cellborder=\"1\" cellspacing=\"0\"><tr><td>$ancestor</td></tr></table> >];";
					$dot .= sprintf(
						"\t\"%s\" -> \"%s\";\n",
						$ancestor,
						$current
					);
				}
				$dot .= $this->array2dot4descendants( $child, $current );
			}
            elseif ( is_string($child) ) {
                $child = str_replace( '_', ' ', $child );
				$dot .= sprintf(
					"\t\"%s\" -> \"%s\";\n",
					$child,
					$current
				);
			}
		}
		return $dot;
	}

	protected function array2dot4ancestors( $array = array(), $ancestor = "" ) {

		$dot = "";
		foreach ( $array as $current => $parents ) {
            $current = str_replace( '_', ' ', $current );
            //$dot .= "\"$current\" [label=< <table  border=\"0\" cellborder=\"1\" cellspacing=\"0\"><tr><td>$current</td></tr></table> >];";
			if ( count($parents) == 0 ) {
                if ( $ancestor ) {
                    $ancestor = str_replace( '_', ' ', $ancestor );
                    //$dot .= "\"$ancestor\" [label=< <table border=\"0\" cellborder=\"1\" cellspacing=\"0\"><tr><td>$ancestor</td></tr></table> >];";
					$dot .= sprintf(
						"\t\"%s\" -> \"%s\";\n",
						$current,
						$ancestor
					);
				}
			}
			else {
				$dot .= $this->array2dot4ancestors( $parents, $current );
				if ( $ancestor ) {
                    $ancestor = str_replace( '_', ' ', $ancestor );
					$dot .= sprintf(
						"\t\"%s\" -> \"%s\";\n",
						$current,
						$ancestor
					);
				}
			}
		}
		return $dot;
	}


}
