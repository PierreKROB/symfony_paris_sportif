<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DataDragonService
{
    private const BASE_URL = 'https://ddragon.leagueoflegends.com';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
    ) {}

    public function getLatestVersion(): string
    {
        return $this->cache->get('ddragon_version', function (ItemInterface $item) {
            $item->expiresAfter(86400); // 24h
            $response = $this->httpClient->request('GET', self::BASE_URL . '/api/versions.json');
            $versions = $response->toArray();
            return $versions[0];
        });
    }

    public function getAllChampions(): array
    {
        $version = $this->getLatestVersion();
        return $this->cache->get('ddragon_champions_' . $version, function (ItemInterface $item) use ($version) {
            $item->expiresAfter(86400);
            $response = $this->httpClient->request('GET',
                self::BASE_URL . "/cdn/{$version}/data/fr_FR/champion.json"
            );
            $data = $response->toArray();
            return $data['data'] ?? [];
        });
    }

    public function getChampion(string $championId): ?array
    {
        $version = $this->getLatestVersion();
        $cacheKey = 'ddragon_champion_' . $version . '_' . strtolower($championId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($version, $championId) {
            $item->expiresAfter(86400);
            try {
                $response = $this->httpClient->request('GET',
                    self::BASE_URL . "/cdn/{$version}/data/fr_FR/champion/{$championId}.json"
                );
                $data = $response->toArray();
                return $data['data'][$championId] ?? null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    public function getChampionIconUrl(string $championId): string
    {
        $version = $this->getLatestVersion();
        return self::BASE_URL . "/cdn/{$version}/img/champion/{$championId}.png";
    }

    public function getSplashUrl(string $championId, int $skinNum = 0): string
    {
        return self::BASE_URL . "/cdn/img/champion/splash/{$championId}_{$skinNum}.jpg";
    }

    public function getProfileIconUrl(int $iconId): string
    {
        $version = $this->getLatestVersion();
        return self::BASE_URL . "/cdn/{$version}/img/profileicon/{$iconId}.png";
    }
}
