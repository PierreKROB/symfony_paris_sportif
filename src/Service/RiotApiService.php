<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RiotApiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface      $cache,
        private LoggerInterface     $logger,
        private string $apiKey,
        private string $platform,  // euw1
        private string $region,    // europe
    ) {}

    private function platformUrl(): string
    {
        return "https://{$this->platform}.api.riotgames.com";
    }

    private function regionalUrl(): string
    {
        return "https://{$this->region}.api.riotgames.com";
    }

    private function get(string $url, int $ttl = 300): array
    {
        $cacheKey = 'riot_' . md5($url);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($url, $ttl) {
            $item->expiresAfter($ttl);
            $this->logger->info('[Riot API] --> GET ' . $url);
            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => ['X-Riot-Token' => $this->apiKey],
                ]);
                $status = $response->getStatusCode();
                $body   = $response->getContent(false);
                $this->logger->info("[Riot API] <-- HTTP {$status} | " . substr($body, 0, 300));
                if ($status !== 200) {
                    throw new \RuntimeException("Riot API HTTP {$status}: {$body}");
                }
                return $response->toArray();
            } catch (\Throwable $e) {
                $item->expiresAfter(0); // ne cache pas les erreurs
                throw $e;
            }
        });
    }

    // ── Account (Riot ID — nouveau format gameName#tagLine) ───────────────────

    /**
     * Résout un Riot ID (ex: "Caps#EUW") en compte Riot → retourne puuid, gameName, tagLine.
     */
    public function getAccountByRiotId(string $gameName, string $tagLine): ?array
    {
        try {
            $g = rawurlencode($gameName);
            $t = rawurlencode($tagLine);
            return $this->get($this->regionalUrl() . "/riot/account/v1/accounts/by-riot-id/{$g}/{$t}");
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot API] getAccountByRiotId failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Summoner ──────────────────────────────────────────────────────────────

    /**
     * Accepte "Caps#EUW" (Riot ID) ou "Caps" (ancien summoner name).
     * Retourne l'objet summoner avec puuid, id, name, profileIconId, summonerLevel.
     */
    public function resolveSummoner(string $input): ?array
    {
        $input = trim($input);

        // Riot ID format: gameName#tagLine
        if (str_contains($input, '#')) {
            [$gameName, $tagLine] = explode('#', $input, 2);
            $account = $this->getAccountByRiotId($gameName, $tagLine);
            if (!$account) return null;

            // Récupère le summoner depuis le puuid
            $summoner = $this->getSummonerByPuuid($account['puuid']);
            if (!$summoner || empty($summoner['puuid'])) {
                $this->logger->warning('[Riot API] summoner-v4/by-puuid failed for puuid ' . $account['puuid']);
                return null;
            }
            // Riot API 2024 : id/name/accountId supprimés, puuid est l'identifiant universel
            $summoner['riotId']   = $input;
            $summoner['gameName'] = $account['gameName'];
            $summoner['tagLine']  = $account['tagLine'];
            $summoner['name']     = $account['gameName'];
            return $summoner;
        }

        // Ancien summoner name (fallback)
        return $this->getSummonerByName($input);
    }

    public function getSummonerByName(string $summonerName): ?array
    {
        try {
            $encoded = rawurlencode($summonerName);
            return $this->get($this->platformUrl() . "/lol/summoner/v4/summoners/by-name/{$encoded}");
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot API] getSummonerByName failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getSummonerByPuuid(string $puuid): ?array
    {
        try {
            return $this->get($this->platformUrl() . "/lol/summoner/v4/summoners/by-puuid/{$puuid}");
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot API] getSummonerByPuuid failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Match history ─────────────────────────────────────────────────────────

    /** @return string[] */
    public function getMatchIds(string $puuid, int $count = 5): array
    {
        try {
            $url = $this->regionalUrl() . "/lol/match/v5/matches/by-puuid/{$puuid}/ids?count={$count}&type=ranked";
            return $this->get($url, 120);
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot API] getMatchIds failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getMatch(string $matchId): ?array
    {
        try {
            return $this->get($this->regionalUrl() . "/lol/match/v5/matches/{$matchId}", 3600);
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot API] getMatch failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Spectator (live game) ─────────────────────────────────────────────────

    public function getLiveGame(string $summonerId): ?array
    {
        try {
            return $this->get(
                $this->platformUrl() . "/lol/spectator/v5/active-games/by-summoner/{$summonerId}",
                30
            );
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot API] getLiveGame failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Ranked stats ──────────────────────────────────────────────────────────

    public function getRankedStats(string $puuid): array
    {
        try {
            return $this->get(
                $this->platformUrl() . "/lol/league/v4/entries/by-puuid/{$puuid}",
                600
            );
        } catch (\Throwable $e) {
            $this->logger->warning('[Riot API] getRankedStats failed: ' . $e->getMessage());
            return [];
        }
    }
}
