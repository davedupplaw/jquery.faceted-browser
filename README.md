# Draggable Facet Browser for jQuery
5th December 2013

This package contains the jQuery facet browser plugin and
a server-side script in PHP for resolving the facets.

A facet browser is a browser similar to the `Artist > Album`
type of browser you might see in iTunes.  This one has a difference
in that you can choose in what order the facets are browsed
using drag and drop client-side.

This codebase contains the jQuery widget for the draggable
browser which generates GET calls to an API, as well as an implementation
of that API using PHP.  You are welcome to use your own implementation.

For information about the code, how to use it and see it in action, go to:
http://david.dupplaw.me.uk/developer/jquery-facets/

## TO GET THE DEMO GOING:

The demo uses an sqlite database which is not included in
this package simply for size efficiency. Download the SQLite
database from here:
http://david.dupplaw.me.uk/files/CarInfo.sqlite

Put this file into the same directory as you've extracted
this package to. It's a 1.8Mb download.

Next you need to include a couple of dependencies for the PHP
part. The dependencies are managed using composer, so download
and run composer:

```
	# Install Composer
	curl -sS https://getcomposer.org/installer | php
	# Run Composer
	php composer.phar install
```

If you unpacked to a web-directory, you're done. Just go to
index.html. Otherwise, you can use PHP5's built-in web-server
to see the demo:

```
	php -S localhost:8000
```

Then browse to http://localhost:8000

For information about the code and how to use it, see:
http://david.dupplaw.me.uk/developer/jquery-facets/
