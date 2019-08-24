<?php

namespace App\Cluster;

use App\Parser\SerpParser;

class Clusterer
{
    /** @var SerpParser */
    private $serpParser;

    public function __construct(SerpParser $serpParser)
    {
        $this->serpParser = $serpParser;
    }

    public function getClusters($minIntersectUrls = 4): array // clusterUrlHashes => [keyword1, keyword2, ...]
    {
        $clusters = [];

        $unclusteredKeywordKeys = array_flip($this->serpParser->getKeywords());
        for ($targetIntersectUrls = 10; $targetIntersectUrls >= $minIntersectUrls; $targetIntersectUrls--) {
            echo $targetIntersectUrls  . "\n";
            $keywords = array_keys($unclusteredKeywordKeys);
            $countKeywords = count($keywords);
            /** @noinspection ForeachInvariantsInspection */
            for ($i = 0; $i < $countKeywords; $i++) {
                $urlHashes1 = $this->serpParser->getKeywordUrlHashes($keywords[$i]);
                for ($j = $i + 1; $j < $countKeywords; $j++) {
                    $urlHashes2 = $this->serpParser->getKeywordUrlHashes($keywords[$j]);
                    $intersection = array_intersect($urlHashes1, $urlHashes2);
                    if (count($intersection) >= $targetIntersectUrls) {
                        ksort($intersection);
                        $key = implode(',', $intersection);
                        if (!isset($clusters[$key])) {
                            $clusters[$key] = [];
                        }
                        $clusters[$key][$keywords[$i]] = true;
                        $clusters[$key][$keywords[$j]] = true;

                        unset($unclusteredKeywordKeys[$keywords[$i]], $unclusteredKeywordKeys[$keywords[$j]]);
                    }
                }
            }
        }


        return $clusters;
    }
}