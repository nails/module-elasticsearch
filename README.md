# Elasticsearch Module for Nails

![license](https://img.shields.io/badge/license-MIT-green.svg)
[![CircleCI branch](https://img.shields.io/circleci/project/github/nails/module-elasticsearch.svg)](https://circleci.com/gh/nails/module-elasticsearch)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nails/module-elasticsearch/badges/quality-score.png)](https://scrutinizer-ci.com/g/nails/module-elasticsearch)
[![Join the chat on Slack!](https://now-examples-slackin-rayibnpwqe.now.sh/badge.svg)](https://nails-app.slack.com/shared_invite/MTg1NDcyNjI0ODcxLTE0OTUwMzA1NTYtYTZhZjc5YjExMQ)

This is the Elasticsearch Module for Nails, it provides a Nails friendly interface for working with the [Elasticsearch PHP client](https://github.com/elastic/elasticsearch-php).

http://nailsapp.co.uk/modules/elasticsearch


## Installing Elasticsearch

You should follow the latest instructions for installation on [Elastic's official documentation page](https://www.elastic.co/guide/en/elasticsearch/reference/master/_installation.html).


## Configuring your Application

Configure which hosts the client connects to by defining a service property called `hosts`. By default this will connect to `localhost:9200`.

If you wish to override this in your application, create a `services.php` file at `application/services/nails/module-elasticsearch/`.

Example `services.php` below:

```php
/**
 * Include the base services file so that the client can be instantiated,
 * remember you are simply overriding defaults.
 */
$aServices = include 'vendor/nails/module-elasticsearch/services/services.php';

/**
 * Define an array of hosts for the Elasticsearch client to use.
 */
$aServices['properties']['hosts'] = array(
    'http://example.com:1234',
    'http://example.co.uk:9200'
);

/**
 * Remember to return the services array so that the Nails Factory picks it up
 */
return $aServices;
```
