<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Service;

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
