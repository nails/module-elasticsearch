<?php

return array(
    'services' => array(
        'Client' => function () {
            return new \Nails\Elasticsearch\Library\Client();
        }
    )
);
