<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Service;

/*
 * This file is part of the CodeQ.AssetSearch package.
 * Most of the code is based on the Flowpack.ElasticSearch.ContentRepositoryAdaptor and the Neos.ContentRepository.Search package.
 *
 * (c) Contributors of the Neos Project - www.neos.io and Code Q Web Factory - www.codeq.at
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class IndexNameService
{
    public const INDEX_PART_SEPARATOR = '-';

    /**
     * @param array $indexNames
     * @param string $postfix
     * @return array
     */
    public static function filterIndexNamesByPostfix(array $indexNames, string $postfix): array
    {
        return array_values(array_filter($indexNames, static function ($indexName) use ($postfix) {
            $postfixWithSeparator = self::INDEX_PART_SEPARATOR . $postfix;
            return substr($indexName, -strlen($postfixWithSeparator)) === $postfixWithSeparator;
        }));
    }
}
