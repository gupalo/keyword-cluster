<?php

namespace App\Cluster;

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

    public function getClusters(int $minIntersectUrls = 4): array // clusterUrlHashes => [keyword1, keyword2, ...]
    {
        $this->unclusteredKeywordKeys = $this->getKeywords();
        $this->minIntersectUrls = $minIntersectUrls;

        $clusters = $this->getFirstPassClusters();
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
            /** @noinspection ForeachInvariantsInspection */
            for ($i = 0; $i < $countKeywords; $i++) {
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
        for ($targetIntersectUrls = 10; $targetIntersectUrls >= $this->minIntersectUrls; $targetIntersectUrls--) {
            $unclusteredClusters = $clusters;
            $result = [];

            $clusterKeys = array_keys($clusters);
            $countClusters = count($clusters);
            $skipIndexKeys = [];
            for ($i = 0; $i < $countClusters; $i++) {
                if (isset($skipIndexKeys[$i])) {
                    continue;
                }

                $clusterUrls1 = explode(',', $clusterKeys[$i]);
                for ($j = $i + 1; $j < $countClusters; $j++) {
                    if (isset($skipIndexKeys[$j])) {
                        continue;
                    }
                    $clusterUrls2 = explode(',', $clusterKeys[$j]);

                    $intersection = array_intersect($clusterUrls1, $clusterUrls2);
                    if (count($intersection) >= $targetIntersectUrls) {
                        ksort($intersection);
                        $clusterUrls1 = $intersection;
                        $key = implode(',', $clusterUrls1);
                        if (!isset($result[$key])) {
                            $result[$key] = [];
                        }
                        $result[$key] = array_merge($result[$key], $clusters[$clusterKeys[$i]], $clusters[$clusterKeys[$j]]);

                        unset($unclusteredClusters[$clusterKeys[$i]], $unclusteredClusters[$clusterKeys[$j]]);
                        $skipIndexKeys[$j] = true;
                    }
                }
            }

            foreach ($unclusteredClusters as $key => $value) {
                $result[$key] = $value;
            }

            $clusters = $result;
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
