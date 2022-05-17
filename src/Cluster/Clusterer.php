<?php

namespace App\Cluster;

use App\Log\Logger;
use App\Parser\KeywordParser;
use App\Parser\SerpParser;

class Clusterer
{
    /** @var SerpParser */
    private $serpParser;

    private $unclusteredKeywordKeys;

    private $minIntersectUrls;

    /** @var KeywordParser */
    private $keywordParser;

    public function __construct(SerpParser $serpParser, KeywordParser $keywordParser)
    {
        $this->serpParser = $serpParser;
        $this->keywordParser = $keywordParser;
    }

    public function getClusters(int $minIntersectUrls = 5): array // clusterUrlHashes => [keyword1, keyword2, ...]
    {
        $this->unclusteredKeywordKeys = $this->getKeywords();
        $this->minIntersectUrls = $minIntersectUrls;

        Logger::log('  Getting first clusters...');
        $clusters = $this->getFirstPassClusters();
        Logger::log('  Merging clusters...');
        $clusters = $this->mergeClusters($clusters);

        foreach ($clusters as $key => &$value) {
            $value = array_keys($value);
        }
        unset($value);

        $clusters[null] = array_keys($this->unclusteredKeywordKeys);

        return $clusters;
    }

    private function getFirstPassClusters(): array
    {
        $clusters = [];
        for ($targetIntersectUrls = 10; $targetIntersectUrls >= $this->minIntersectUrls; $targetIntersectUrls--) {
            $keywords = array_keys($this->unclusteredKeywordKeys);
            $countKeywords = count($keywords);
            $skipIndexKeys = [];
            Logger::log('    Pass: ' . $targetIntersectUrls . ' Keywords: ' . $countKeywords);
            for ($i = 0; $i < $countKeywords; $i++) {
                if (($i+1) % 1000 === 0) {
                    echo (($i+1) % 10000 === 0) ? '#' : '.';
                }
                if (isset($skipIndexKeys[$i])) {
                    continue;
                }

                $urlHashes1 = $this->serpParser->getKeywordUrlHashes($keywords[$i]);
                for ($j = $i + 1; $j < $countKeywords; $j++) {
                    if (isset($skipIndexKeys[$j])) {
                        continue;
                    }

                    $urlHashes2 = $this->serpParser->getKeywordUrlHashes($keywords[$j]);
                    $intersection = array_intersect($urlHashes1, $urlHashes2);
                    if (count($intersection) >= $targetIntersectUrls) {
                        ksort($intersection);
                        $urlHashes1 = $intersection;
                        $key = implode(',', $intersection);
                        if (!isset($clusters[$key])) {
                            $clusters[$key] = [];
                        }
                        $clusters[$key][$keywords[$i]] = true;
                        $clusters[$key][$keywords[$j]] = true;

                        unset($this->unclusteredKeywordKeys[$keywords[$i]], $this->unclusteredKeywordKeys[$keywords[$j]]);
                        $skipIndexKeys[$j] = true;
                    }
                }
            }
        }

        return $clusters;
    }

    private function mergeClusters(array $clusters): array
    {
        $infinity = 10;
        for ($targetIntersectUrls = 10; $targetIntersectUrls >= $this->minIntersectUrls; $targetIntersectUrls--) {
            $isDirty = false;
            $unclusteredClusters = $clusters;
            $result = [];

            $clusterKeys = array_keys($clusters);
            $countClusters = count($clusters);
            $skipIndexKeys = [];
            Logger::log('    Pass: ' . $targetIntersectUrls . ' Clusters: ' . $countClusters);
            for ($i = 0; $i < $countClusters; $i++) {
                if (($i+1) % 100 === 0) {
                    echo (($i+1) % 1000 === 0) ? '#' : '.';
                }
                if (isset($skipIndexKeys[$i])) {
                    continue;
                }

                $key1 = $clusterKeys[$i];
                $urls1 = explode(',', $key1);
                for ($j = $i + 1; $j < $countClusters; $j++) {
                    if (isset($skipIndexKeys[$j])) {
                        continue;
                    }
                    $key2 = $clusterKeys[$j];
                    $urls2 = explode(',', $key2);

                    $intersection = array_intersect($urls1, $urls2);
                    if (count($intersection) >= $targetIntersectUrls) {
                        ksort($intersection);
                        $key = implode(',', $intersection);
                        if (!isset($result[$key])) {
                            $result[$key] = [];
                        }
                        $result[$key] = array_merge($result[$key], $clusters[$key1], $clusters[$key2]);
                        Logger::log(sprintf("        Merging clusters %s+%s; keywords %s = %s & %s", $i, $j, count($result[$key]), count($clusters[$key1]), count($clusters[$key2])));

                        unset($unclusteredClusters[$key1], $unclusteredClusters[$key2]);
                        $skipIndexKeys[$j] = true;

                        $isDirty = true;
                    }
                }
            }

            foreach ($unclusteredClusters as $key => $value) {
                $result[$key] = $value;
            }

            $clusters = $result;

            if ($isDirty && $infinity-- > 0) {
                $targetIntersectUrls++;
            } else {
                $infinity = 10;
            }
        }

        return $clusters;
    }

    private function getKeywords(): array
    {
        $keywords = $this->serpParser->getKeywords();

        $result = [];
        foreach ($keywords as $keyword) {
            $result[$keyword] = $this->keywordParser->getVolume($keyword);
        }
        arsort($result);

        return $result;
    }
}
