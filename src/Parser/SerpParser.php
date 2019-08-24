<?php

namespace App\Parser;

use App\Service\Sanitizer;

class SerpParser
{
    /** @var KeywordParser */
    private $keywordParser;

    private $rows = []; // [ [pos => ..., keyword => ..., domain => ..., url => ..., title => ..., count_results => ..., is_misspell => ...] ]

    private $keywordUrlHashKeys = []; // keyword => [urlHash1 => true, urlHash2 => true, ...]

    private $hashUrls = []; // [urlHash => url]

    private const POS_VISIBILITY = [
        1 => 1,
        2 => 1,
        3 => 1,
        4 => 0.85,
        5 => 0.6,
        6 => 0.5,
        7 => 0.5,
        8 => 0.3,
        9 => 0.3,
        10 => 0.2,
        11 => 0.1,
        12 => 0.1,
        13 => 0.1,
        14 => 0.1,
        15 => 0.1,
        16 => 0.05,
        17 => 0.05,
        18 => 0.05,
        19 => 0.05,
        20 => 0.05,
    ];

    public function __construct(KeywordParser $keywordParser)
    {
        $this->keywordParser = $keywordParser;
    }

    public function load(array $serpRows): void
    {
        $pos = 0;
        $lastKeyword = null;
        foreach ($serpRows as $serpRow) {
            $keyword = $serpRow['keyword'];
            $url = $serpRow['url'];
            $volume = $this->keywordParser->getVolume($keyword);

            if ($keyword !== $lastKeyword) {
                $pos = 1;
                $lastKeyword = $keyword;
            } else {
                $pos++;
            }

            $urlHash = md5($url);
            if (!isset($this->hashUrls[$urlHash])) {
                $this->hashUrls[$urlHash] = $url;
            }

            if (!isset($this->keywordUrlHashKeys[$keyword])) {
                $this->keywordUrlHashKeys[$keyword] = [];
            }
            $this->keywordUrlHashKeys[$keyword][$urlHash] = true;

            $domain = UrlParser::extractDomain($url);

            $this->rows[] = [
                'pos' => $pos,
                'keyword' => $keyword,
                'domain' => $domain,
                'url' => $url,
                'title' => $serpRow['title'],
                'count_results' => $serpRow['count_results'],
                'is_misspell' => $serpRow['is_misspell'],
                'volume' => $volume,
                'visibility' => $volume * (self::POS_VISIBILITY[$pos] ?? 0),
            ];
        }
    }

    public function getUrlByHash(?string $urlHash): ?string
    {
        return $this->hashUrls[$urlHash] ?? null;
    }

    public function getKeywordUrlHashes(string $keyword): array
    {
        return array_keys($this->keywordUrlHashKeys[$keyword]) ?? [];
    }

    public function getKeywords(): array
    {
        return array_keys($this->keywordUrlHashKeys);
    }

    public function getDomainVisibility(): array
    {
        $result = [];
        foreach ($this->rows as $row) {
            $domain = Sanitizer::safeListItem($row['domain']);
            $url = Sanitizer::safeListItem($row['url']);
            $keyword = Sanitizer::safeListItem($row['keyword']);
            $visibility = $row['visibility'];

            if (!isset($result[$domain])) {
                $result[$domain] = [
                    'keywords' => [],
                    'pages' => [],
                    'visibility' => 0,
                ];
            }
            $result[$domain]['keywords'][$keyword] = $visibility;
            $result[$domain]['pages'][$url] = $visibility;
            $result[$domain]['visibility'] += $visibility;
        }
        uasort($result, static function ($a, $b){
            if ($a['visibility'] !== $b['visibility']) {
                return $b['visibility'] <=> $a['visibility'];
            }

            return count($b['keywords']) <=> count($a['keywords']);
        });
        foreach ($result as &$item) {
            arsort($item['keywords']);
            arsort($item['pages']);
        }
        unset($item);

        return $result;
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}
