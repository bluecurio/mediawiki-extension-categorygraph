<?php

class CategoryGraphApi extends ApiBase {	
	
	 public function execute() {	

        // get some parameters
		$params = $this->extractRequestParams();
		$this->validateParams( $params );

        // validate the things we need
		$title = Title::newFromText( $params['title'] ); 
		if ( !$title ) {
			$this->dieUsage( wfMsg( 'category-graph-bad-title', $params['title'] ), 1 );
		}
		if ( !$title->exists() ) {
			$this->dieUsage( wfMsg( 'category-graph-title-does-not-exist', $params['title'] ) , 2) ;		
		}

        // make a new graph object
        $graph = new CategoryGraph( $title );

        // add the ajax-params
        $graph->addAjaxParams( $params );

        // get the response and add it to the result
        $this->getResult()->addValue( 
            'result', 
            'html', 
            $graph->ajaxResponse()
        );

        // all done!
	}

	public function getVersion() {
		return __CLASS__ . ': ' . CATEGORY_GRAPH_VERSION;
	}	

	protected function validateParams( $params ) {
		if ( !isSet($params['title']) ) {
			$this->dieUsageMsg( array('missingparam', 'title') );
		}
	}

	public function getParamDescription() {
		return array(
            'title' => 'The category to use as the main node of the graph.',
            'max_descendants' => 'The maximum number of descendant categories to show in the graph.',
            'max_ancestors' => 'The maximum number of ancestor categories to show in the graph.',
            'format-for-download' => 'The format of the image to download.'
		);
	}
	
	public function getDescription() {
		return array(
			'Displays a graph of category heirachy for a given category.'
		);
	}
	
	public function getAllowedParams() {
		return array(
            'title' => null,
            'max_descendants' => -1,             // -1 means unlimited
            'max_ancestors' => -1,
            'format-for-download' => null
		);
	}
	
	public function getPossibleErrors() {
		return array_merge( 
			parent::getPossibleErrors(), 
			array(
				array( 'missingparam', 'title' )
			)
		);
	}
	
}
