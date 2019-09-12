<?php

namespace App\Command;

use App\Cluster\Clusterer;
use App\Log\Logger;
use App\Parser\KeywordParser;
use App\Parser\SerpParser;
use App\Service\Sanitizer;
use App\Service\TsvFileReader;
use App\Service\TsvFileWriter;

class DefaultCommand
{
    /** @var SerpParser */
    private $serpParser;

    /** @var KeywordParser */
    private $keywordParser;

    /** @var array */
    private $clusters;

    /** @var string */
    private $serpFilename;

    /** @var string */
    private $keywordsFilename;

    /** @var string */
    private $clustersFilename;

    /** @var string */
    private $outputSerpFilename;

    /** @var string */
    private $competitorsFilename;

    public function __construct()
    {
        $varDir = dirname(__DIR__, 2) . '/var';
        // input
        $this->serpFilename = $varDir . '/input/serp.txt';
        $this->keywordsFilename = $varDir . '/input/keywords.txt';
        // output
        $this->outputSerpFilename = $varDir . '/output/serp.txt';
        $this->competitorsFilename = $varDir . '/output/competitors.txt';
        $this->clustersFilename = $varDir . '/output/clusters.txt';
    }

    public function execute(): void
    {
        Logger::log('Loading...');
        $this->load();
        Logger::log('Clustering...');
        $this->cluster();
        Logger::log('Saving...');
        $this->save();

        Logger::log('OK');
    }

    private function load(): void
    {
        $keywordRows = TsvFileReader::read($this->keywordsFilename, ['keyword', 'volume']);
        $this->keywordParser = new KeywordParser();
        $this->keywordParser->load($keywordRows);

        $serpRows = TsvFileReader::read($this->serpFilename, ['keyword', 'url', 'title', 'count_results', 'is_misspell']);
        $this->serpParser = new SerpParser($this->keywordParser);
        $this->serpParser->load($serpRows);
    }

    private function cluster(): void
    {
        $clusterer = new Clusterer($this->serpParser, $this->keywordParser);
        $this->clusters = $clusterer->getClusters(4);
    }

    private function save(): void
    {
        $this->saveSerp();
        $this->saveCompetitors();
        $this->saveClusters();
    }

    private function saveClusters(): void
    {
        $resultItems = [];
        foreach ($this->clusters as $urlHashes => $keywords) {
            $isUnclustered = ($urlHashes === '');
            $urlHashes = explode(',', $urlHashes);
            $urls = [];
            foreach ($urlHashes as $urlHash) {
                $url = $this->serpParser->getUrlByHash($urlHash);
                $urls[] = Sanitizer::safeListItem($url);
            }

            $keywordVolumes = [];
            foreach ($keywords as $keyword) {
                $keywordVolumes[$keyword] = $this->keywordParser->getVolume($keyword);
            }
            arsort($keywordVolumes);

            $clusterName = $isUnclustered ? 'unclustered' : array_keys($keywordVolumes)[0];
            $totalKeywords = count($keywords);
            $totalVolume = array_sum($keywordVolumes);

            foreach ($keywordVolumes as $keyword => $volume) {
                $resultItems[] = [
                    'cluster' => $clusterName,
                    'total_keywords' => $totalKeywords,
                    'total_volume' => $totalVolume,
                    'keyword' => $keyword,
                    'volume' => $volume,
                    'urls' => implode(', ', $urls),
                    'urls_count' => count($urls),
                ];
            }
        }

        TsvFileWriter::write($this->clustersFilename, $resultItems);
    }

    private function saveSerp(): void
    {
        TsvFileWriter::write($this->outputSerpFilename, $this->serpParser->getRows());
    }

    private function saveCompetitors(): void
    {
        $rows = [];
        $domains = $this->serpParser->getDomainVisibility();
        foreach ($domains as $domain => $value) {
            $topKeywords = array_slice(array_keys($value['keywords']), 0, 5);
            $topPages = array_slice(array_keys($value['pages']), 0, 5);

            $isHttps = '-';
            if (isset($topPages[0])) {
                $isHttps = preg_match('#^https://#i', $topPages[0]) ? '1' : '0';
            }
            $isWww = preg_match('#^www\.#i', $domain) ? '1' : '0';
            $tld = $this->extractTld($domain);

            $rows[] = [
                'domain' => preg_replace('#^www\.#i', '', $domain),
                'visibility' => $value['visibility'],
                'keywords' => count($value['keywords']),
                'is_https' => $isHttps,
                'is_www' => $isWww,
                'tld' => $tld,
                'keyword_1' => $topKeywords[0] ?? '',
                'keyword_2' => $topKeywords[1] ?? '',
                'keyword_3' => $topKeywords[2] ?? '',
                'keyword_4' => $topKeywords[3] ?? '',
                'keyword_5' => $topKeywords[4] ?? '',
                'page_1' => preg_replace('#^[a-zA-Z\d]+://' . preg_quote($domain, '#') . '#', '', $topPages[0] ?? ''),
                'page_2' => preg_replace('#^[a-zA-Z\d]+://' . preg_quote($domain, '#') . '#', '', $topPages[1] ?? ''),
                'page_3' => preg_replace('#^[a-zA-Z\d]+://' . preg_quote($domain, '#') . '#', '', $topPages[2] ?? ''),
                'page_4' => preg_replace('#^[a-zA-Z\d]+://' . preg_quote($domain, '#') . '#', '', $topPages[3] ?? ''),
                'page_5' => preg_replace('#^[a-zA-Z\d]+://' . preg_quote($domain, '#') . '#', '', $topPages[4] ?? ''),
            ];
        }

        TsvFileWriter::write($this->competitorsFilename, $rows);
    }

    private function extractTld(string $domain): string
    {
        $tld = preg_match('#\.((com|in|net|org|kiev)\.ua|co\.uk|msk\.ru|spb\.ru|com\.ru|org\.ru|net\.ru|[a-z\d\-]+)$#i', $domain, $m) ? ltrim($m[0], '.') : '-';

        $translate = [
            'xn--80asehdb' => 'онлайн',
            'xn--p1ai' => 'рф',
        ];
        if (isset($translate[$tld])) {
            $tld = $translate[$tld];
        }

        return $tld;
    }
}
