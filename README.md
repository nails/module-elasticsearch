# Elasticsearch Module for Nails

This is the Elasticsearch Module for Nails, it provides a Nails friendly interface for working with the [Elasticsearch PHP client](https://github.com/elastic/elasticsearch-php).

http://nailsapp.co.uk/modules/elasticsearch



## Installing Elasticsearch

You should follow the latest instructions for installation on [Elastic's official documentation page](https://www.elastic.co/guide/en/elasticsearch/reference/master/_installation.html).


## Configuring your Application

Configure which hosts the client connects to by defining a service property called `hosts`. By default this will connect to `localhost:9200`.

If you wish to override this in your application, create a `services.php` file at `application/services/nailsapp/module-elasticsearch/`.

Example `services.php` below:

    <?php

    /**
     * Include the base services file so that the client can be instantiated,
     * remember you are simply overriding defaults.
     */
    $aServices = include 'vendor/nailsapp/module-elasticsearch/services/services.php';

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


