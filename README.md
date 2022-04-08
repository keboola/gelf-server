# GELF server [![GitHub Actions](https://github.com/keboola/gelf-server/actions/workflows/push.yml/badge.svg)](https://github.com/keboola/gelf-server/actions/workflows/push.yml)

A php implementation of a [gelf](http://docs.graylog.org/en/2.0/pages/gelf.html) compatible backend like [Graylog2](https://www.graylog.org/). This library provides
a simple server to which a GELF client can connect. 

## Installation

Recommended installation via composer:

Add gelf-server to composer.json either by running `composer require keboola/gelf-server` or by defining it manually:

	"require": {
	   // ...
	   "keboola/gelf-server": "^1.1"
	   // ...
	}

Reinstall dependencies: `composer install`

## Usage

To create a server, use the `ServerFactory` class:

	$server = ServerFactory::createServer(ServerFactory::SERVER_TCP);

To start listening for connections, us the `start` method. This method has the following parameters:

- `$minPort` and `$maxPort` - to set port on which the server listens. To listen on a single port use the same value for
	both `$minPort` and `$maxPort`. Otherwise the server will randomly choose a free port in the specified range (inclusive).
- `$onStart` - Callback executed when the server successfully started listening, the callback signature is `function ($port)`, which
	gives you the actual port the server is listening on.
- `$onProcess` - Callback executed periodically when the server is running. The callback has signature `function (&$terminated)`. 
	The server will keep running indefinitely until you set `$terminated` to true in this callback.
- `$onEvent` - Callback executed when a GELF event is received. The callback has signature `function ($event)`. The 
	`$event` variable contains associative array with [GELF fields](http://docs.graylog.org/en/2.0/pages/gelf.html#gelf-format-specification)
- `$onTerminate` - Optional callback executed when the server terminates - after it stops listening for connections. The callback
	signature is `function ()`.

## Examples

For usage examples, see the [/examples](https://github.com/keboola/gelf-server/tree/master/examples) directory.
