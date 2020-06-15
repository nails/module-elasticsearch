<?php

/**
 * Elasticsearch Query Helper
 *
 * @package     Nails
 * @subpackage  module-elasticsearch
 * @category    Helper
 * @author      Nails Dev Team
 * @link        https://docs.nailsapp.co.uk/modules/other/elasticsearch
 */

namespace Nails\Elasticsearch\Helper;

/**
 * Class Query
 *
 * @package Nails\Elasticsearch\Helper
 */
class Query
{
    /**
     * Reserved characters which should be escaped
     *
     * @var string[]
     */
    const RESERVED_CHARS = [
        '+',
        '-',
        '=',
        '&&',
        '||',
        '>',
        '<',
        '!',
        '(',
        ')',
        '{',
        '}',
        '[',
        ']',
        '^',
        '"',
        '~',
        '*',
        '?',
        ':',
        '\\',
        '/',
    ];

    // --------------------------------------------------------------------------

    /**
     * Escapes special characters from a query string
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#_reserved_characters
     *
     * @param string $sQuery
     *
     * @return string
     */
    public static function escape(string $sQuery): string
    {
        //  > and < cannot be escaped and must be removed
        $sQuery = str_replace(['>', '<'], '', $sQuery);

        $sPattern = implode('|', array_map(
            function ($sItem) {
                return preg_quote($sItem, '/');
            },
            static::RESERVED_CHARS
        ));
        $sPattern = '/(' . $sPattern . ')/';

        return preg_replace($sPattern, '\\\$1', $sQuery);
    }
}
