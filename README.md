# CategoryGraph

This is an experimental [MediaWiki](http://www.mediawiki.org) extension that uses [graphviz](http://www.graphviz.org/) to render images of MediaWiki categories. Some examples can be found in the [t/ directory](http://github.com/bluecurio/CategoryGraph/tree/master/t).

Use at your own risk.

--[Daniel Renfro](http://www.mediawiki.org/wiki/User:DanielRenfro)

## Installation

Install this extension just like any other [MediaWiki extension](https://www.mediawiki.org/wiki/Manual:Extensions). Something like this in your <pre>LocalSettings.php</pre> file:

```PHP
require_once( "$IP/CategoryGraph/CategoryGraph.php" );
```

