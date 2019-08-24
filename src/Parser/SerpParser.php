<?php

namespace App\Parser;

class SerpParser
{
    private $rows = []; // [ [pos => ..., keyword => ..., domain => ..., url => ..., title => ..., count_results => ..., is_misspell => ...] ]

    private $keywordUrlHashKeys = []; // keyword => [urlHash1 => true, urlHash2 => true, ...]

    private $hashUrls = []; // [urlHash => url]

    public function __construct(array $serpRows)
    {
        $pos = 0;
        $lastKeyword = null;
        foreach ($serpRows as $serpRow) {
            $keyword = $serpRow['keyword'];
            $url = $serpRow['url'];

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
            ];
        }
    }

    public function getUrlByHash(string $urlHash): ?string
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
}