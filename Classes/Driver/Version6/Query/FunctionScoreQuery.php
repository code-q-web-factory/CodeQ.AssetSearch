<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6\Query;

use CodeQ\AssetSearch\Exception;
use Neos\Utility\Arrays;

class FunctionScoreQuery extends FilteredQuery
{
    /**
     * @var array
     */
    protected $functionScoreRequest = [
        'functions' => []
    ];

    /**
     * @param array $functions
     * @return void
     */
    public function functions(array $functions): void
    {
        if (isset($functions['functions'])) {
            $this->functionScoreRequest = $functions;
        } else {
            $this->functionScoreRequest['functions'] = $functions;
        }
    }

    /**
     * @param string $scoreMode
     * @return void
     * @throws Exception\QueryBuildingException
     */
    public function scoreMode(string $scoreMode): void
    {
        if (!in_array($scoreMode, ['multiply', 'first', 'sum', 'avg', 'max', 'min'])) {
            throw new Exception\QueryBuildingException('Invalid score mode', 1454016230);
        }
        $this->functionScoreRequest['score_mode'] = $scoreMode;
    }

    /**
     * @param string $boostMode
     * @return void
     * @throws Exception\QueryBuildingException
     */
    public function boostMode(string $boostMode): void
    {
        if (!in_array($boostMode, ['multiply', 'replace', 'sum', 'avg', 'max', 'min'])) {
            throw new Exception\QueryBuildingException('Invalid boost mode', 1454016229);
        }
        $this->functionScoreRequest['boost_mode'] = $boostMode;
    }

    /**
     * @param integer|float $boost
     * @return void
     * @throws Exception\QueryBuildingException
     */
    public function maxBoost($boost): void
    {
        if (!is_numeric($boost)) {
            throw new Exception\QueryBuildingException('Invalid max boost', 1454016230);
        }
        $this->functionScoreRequest['max_boost'] = $boost;
    }

    /**
     * @param integer|float $score
     * @return void
     * @throws Exception\QueryBuildingException
     */
    public function minScore($score): void
    {
        if (!is_numeric($score)) {
            throw new Exception\QueryBuildingException('Invalid max boost', 1454016230);
        }
        $this->functionScoreRequest['min_score'] = $score;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareRequest(): array
    {
        if ($this->functionScoreRequest['functions'] === []) {
            return parent::prepareRequest();
        }
        $currentQuery = $this->request['query'];

        $baseQuery = $this->request;
        unset($baseQuery['query']);

        $functionScore = $this->functionScoreRequest;
        $functionScore['query'] = $currentQuery;
        $query = Arrays::arrayMergeRecursiveOverrule($baseQuery, [
            'query' => [
                'function_score' => $functionScore
            ]
        ]);

        return $query;
    }
}
