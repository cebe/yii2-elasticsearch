<?php
namespace common\modules\elasticsearch\components\queries\Aggregation\Generator;

use common\modules\elasticsearch\components\queries\Aggregation\AggResult;

/**
 * Class DefaultAggGenerator
 * @package common\modules\elasticsearch\components\queries\helpers\Aggregation\Generator
 */
class DefaultAggGenerator implements AggGeneratorInterface
{
    protected $generators = [];

    /**
     * DefaultAggGenerator constructor.
     * @param array $generators
     */
    public function __construct($generators = [])
    {
        if (!isset($this->generators['singleValueGenerator'])) {
            $this->generators['singleValueGenerator'] = function ($aggName, $key = '', $valueField = 'doc_count') {
                return self::singleValueGenerator($aggName, $key, $valueField);
            };
        }

        if (!isset($this->generators['keyedBucketGenerator'])) {
            $this->generators['keyedBucketGenerator'] = function ($aggName) {
                return self::keyedBucketGenerator($aggName);
            };
        }

        if (!isset($this->generators['bucketGenerator'])) {
            $this->generators['bucketGenerator'] = function ($aggName, $keyAsString = false) {
                return self::bucketGenerator($aggName, $keyAsString);
            };
        }
    }

    /**
     * @param $aggName
     * @param string $filterKey
     * @return mixed
     */
    public function getFilterGenerator($aggName, $filterKey = '')
    {
        $generator = isset($this->generators['filter'])
            ? $this->generators['filter']
            : $this->generators['singleValueGenerator'];

        return $generator($aggName, $filterKey);
    }

    /**
     * @param $generator
     */
    public function setFilterGenerator($generator)
    {
        $this->generators['filter'] = $generator;
    }

    /**
     * @param $aggName
     * @return mixed
     */
    public function getFiltersGenerator($aggName)
    {
        $generator = isset($this->generators['filters'])
            ? $this->generators['filters']
            : $this->generators['keyedBucketGenerator'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setFiltersGenerator($generator)
    {
        $this->generators['filters'] = $generator;
    }

    /**
     * @param $aggName
     * @return mixed
     */
    public function getTermsGenerator($aggName)
    {
        $generator = isset($this->generators['terms'])
            ? $this->generators['terms']
            : $this->generators['bucketGenerator'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setTermsGenerator($generator)
    {
        $this->generators['terms'] = $generator;
    }

    /**
     * @param $aggName
     * @return mixed
     */
    public function getAggregationsGenerator($aggName)
    {
        $generator = isset($this->generators['aggs'])
            ? $this->generators['aggs']
            : $this->generators['bucketGenerator'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setAggregationsGenerator($generator)
    {
        $this->generators['aggs'] = $generator;
    }

    /**
     * @param $aggName
     * @param bool $keyAsString
     * @return mixed
     */
    public function getDateHistogramGenerator($aggName, $keyAsString = true)
    {
        $generator = isset($this->generators['dateHistogram'])
            ? $this->generators['dateHistogram']
            : $this->generators['bucketGenerator'];

        return $generator($aggName, $keyAsString);
    }

    /**
     * @param $generator
     */
    public function setDateHistogramGenerator($generator)
    {
        $this->generators['dateHistogram'] = $generator;
    }

    /**
     * @param $aggName
     * @return mixed
     */
    public function getRangeGenerator($aggName)
    {
        $generator = isset($this->generators['range'])
            ? $this->generators['range']
            : $this->generators['bucketGenerator'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setRangeGenerator($generator)
    {
        $this->generators['range'] = $generator;
    }

    /**
     * @param $aggName
     * @param string $filterKey
     * @return mixed
     */
    public function getSumGenerator($aggName, $filterKey = 'Sum')
    {
        $generator = isset($this->generators['sum'])
            ? $this->generators['sum']
            : $this->generators['singleValueGenerator'];

        return $generator($aggName, $filterKey, 'value');
    }

    /**
     * @param $generator
     */
    public function setSumGenerator($generator)
    {
        $this->generators['sum'] = $generator;
    }

    /**
     * @param $aggName
     * @return mixed
     */
    public function getNestedGenerator($aggName)
    {
        $generator = isset($this->generators['nested'])
            ? $this->generators['nested']
            : $this->generators['singleValueGenerator'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setNestedGenerator($generator)
    {
        $this->generators['nested'] = $generator;
    }

    /**
     * @param $aggName
     * @return mixed
     */
    public function getReverseNestedGenerator($aggName)
    {
        $generator = isset($this->generators['reverseNested'])
            ? $this->generators['reverseNested']
            : $this->generators['singleValueGenerator'];

        return $generator($aggName);
    }

    /**
     * @param $generator
     */
    public function setReverseNestedGenerator($generator)
    {
        $this->generators['reverseNested'] = $generator;
    }

    /**
     * @param $generator
     */
    public function setSingleValueGenerator($generator)
    {
        $this->generators['singleValueGenerator'] = $generator;
    }

    /**
     * @param $generator
     */
    public function setBucketGenerator($generator)
    {
        $this->generators['bucketGenerator'] = $generator;
    }

    /**
     * @param $generator
     */
    public function setKeyedBucketGenerator($generator)
    {
        $this->generators['keyedBucketGenerator'] = $generator;
    }

    /**
     * @param string $aggName
     * @param string $key
     * @param string $valueField
     * @return callable
     */
    public static function singleValueGenerator($aggName, $key = '', $valueField = 'doc_count')
    {
        if ($key === '') {
            $generator = function ($results) use ($aggName, $valueField) {
                yield new AggResult($results[$aggName][$valueField], $results[$aggName]);
            };
        } else {
            $generator = function ($results) use ($aggName, $key, $valueField) {
                yield $key => new AggResult($results[$aggName][$valueField], $results[$aggName]);
            };
        }

        return $generator;
    }

    /**
     * @param string $aggName
     * @param bool $keyAsString
     * @return callable
     */
    public static function bucketGenerator($aggName, $keyAsString = false)
    {
        // should be more efficient to have the logic outside of the generator and loop
        if ($keyAsString) {
            $generator = function ($results) use ($aggName) {
                if(isset($results[$aggName]['buckets'])) {
                    foreach ($results[$aggName]['buckets'] as $bucket) {
                        yield $bucket['key_as_string'] => new AggResult($bucket['doc_count'], $bucket);
                    }
                }
                if(isset($results[$aggName]['hits'])){
                    yield $aggName => new AggResult($results[$aggName], $results[$aggName]['hits']['hits']);
                }
            };
        } else {
            $generator = function ($results) use ($aggName) {
                if(isset($results[$aggName]['buckets'])) {
                    foreach ($results[$aggName]['buckets'] as $bucket) {
                        yield $bucket['key'] => new AggResult($bucket['doc_count'], $bucket);
                    }
                }
                if(isset($results[$aggName]['hits'])){
                    yield $aggName => new AggResult($results[$aggName], $results[$aggName]['hits']['hits']);
                }
            };
        }

        return $generator;
    }

    /**
     * @param string $aggName
     * @return callable
     */
    public static function keyedBucketGenerator($aggName)
    {
        $generator = function ($results) use ($aggName) {
            foreach ($results[$aggName]['buckets'] as $key => $bucket) {
                yield $key => new AggResult($bucket['doc_count'], $bucket);
            }
        };

        return $generator;
    }
}
