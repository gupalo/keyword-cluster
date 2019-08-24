<?php

namespace App\Command;

use App\Cluster\Clusterer;
use App\Parser\KeywordParser;
use App\Parser\SerpParser;
use App\Service\TsvFileReader;
use App\Service\TsvFileWriter;

class DefaultCommand
{
    public function execute(): void
    {
        $varDir = dirname(__DIR__, 2) . '/var';
        // input
        $serpFilename = $varDir . '/input/serp.txt';
        $keywordsFilename = $varDir . '/input/keywords.txt';
        // output
        $clustersFilename = $varDir . '/output/clusters.txt';


        $serpRows = TsvFileReader::read($serpFilename, ['keyword', 'url', 'title', 'count_results', 'is_misspell']);
        $serpParser = new SerpParser($serpRows);

        $keywordRows = TsvFileReader::read($keywordsFilename, ['keyword', 'volume']);
        $keywordParser = new KeywordParser($keywordRows);

        $clusterer = new Clusterer($serpParser);
        $clusters = $clusterer->getClusters(4);
        print_r($clusters);
        exit;

//        foreach ($keywordUrls as $keyword => $hashes) {
//            $clusterName = md5(implode(',', $hashes));
//            if (!isset($cluster[$clusterName])) {
//                $cluster[$clusterName] = [];
//            }
//            $clusters[$clusterName][] = [
//                'keyword' => $keyword,
//                'volume' => ($keywords[$keyword] ?? ['volume' => -1])['volume'],
//            ];
//        }
//
//        $resultItems = [];
//        foreach ($clusters as $clusterName => $clusterItems) {
//            uasort($clusterItems, static function($a, $b) {
//                return $a['volume'] <=> $b['volume'];
//            });
//            $countItems = count($clusterItems);
//            $sumVolume = 0;
//            foreach ($clusterItems as $clusterItem) {
//                $sumVolume += $clusterItem['volume'];
//            }
//
//            foreach ($clusterItems as $clusterItem) {
//                $resultItems[] = [
//                    'cluster' => $clusterName,
//                    'total_keywords' => $countItems,
//                    'total_volume' => $sumVolume,
//                    'keyword' => $clusterItem['keyword'],
//                    'volume' => $clusterItem['volume'],
//                ];
//            }
//        }
//
//        TsvFileWriter::write($clustersFilename, $resultItems);
//
//
//
//        echo(count($resultItems) . "\n\n");


        /*
        игровые автоматы бесплатно
        https://xn---7-6kcaibery9breu7ad0li.xn--80asehdb/igrat-besplatno/
        <div class="ellip">Игровые автоматы играть бесплатно (без регистраций и СМС ...</div>
        8180000
        0
        */
    }
}