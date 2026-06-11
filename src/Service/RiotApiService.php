<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RiotApiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
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
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['X-Riot-Token' => $this->apiKey],
            ]);
            return $response->toArray();
        });
    }

    // ── Summoner ──────────────────────────────────────────────────────────────

    public function getSummonerByName(string $summonerName): ?array
    {
        try {
            $encoded = rawurlencode($summonerName);
            return $this->get($this->platformUrl() . "/lol/summoner/v4/summoners/by-name/{$encoded}");
        } catch (\Throwable) {
            return null;
        }
    }

    public function getSummonerByPuuid(string $puuid): ?array
    {
        try {
            return $this->get($this->platformUrl() . "/lol/summoner/v4/summoners/by-puuid/{$puuid}");
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Match history ─────────────────────────────────────────────────────────

    /**
     * @return string[] list of matchIds
     */
    public function getMatchIds(string $puuid, int $count = 5): array
    {
        try {
            $url = $this->regionalUrl() . "/lol/match/v5/matches/by-puuid/{$puuid}/ids?count={$count}&type=ranked";
            return $this->get($url, 120);
        } catch (\Throwable) {
            return [];
        }
    }

    public function getMatch(string $matchId): ?array
    {
        try {
            return $this->get($this->regionalUrl() . "/lol/match/v5/matches/{$matchId}", 3600);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Build a bet-friendly event from a match:
     * teamA = winner team, teamB = loser team, already played.
     */
    public function buildEventFromMatch(array $match): array
    {
        $info = $match['info'];
        $teams = $info['teams'];

        $winner = null;
        $loser  = null;
        foreach ($teams as $team) {
            if ($team['win']) {
                $winner = $team;
            } else {
                $loser = $team;
            }
        }

        // Build participant summary per team
        $participants = [];
        foreach ($info['participants'] as $p) {
            $participants[$p['teamId']][] = $p['summonerName'] ?? $p['riotIdGameName'];
        }

        $winTeamId  = $winner['teamId'] ?? 100;
        $loseTeamId = $loser['teamId'] ?? 200;

        return [
            'matchId'    => $match['metadata']['matchId'],
            'teamA'      => 'Équipe Bleue',
            'teamB'      => 'Équipe Rouge',
            'teamAWin'   => $winTeamId === 100,
            'duration'   => $info['gameDuration'],
            'playedAt'   => (new \DateTime())->setTimestamp((int)($info['gameEndTimestamp'] / 1000)),
            'teamAPlayers' => implode(', ', $participants[100] ?? []),
            'teamBPlayers' => implode(', ', $participants[200] ?? []),
        ];
    }

    // ── Spectator (live game) ─────────────────────────────────────────────────

    public function getLiveGame(string $summonerId): ?array
    {
        try {
            return $this->get(
                $this->platformUrl() . "/lol/spectator/v5/active-games/by-summoner/{$summonerId}",
                30 // très court TTL — c'est du live
            );
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Ranked stats ──────────────────────────────────────────────────────────

    public function getRankedStats(string $summonerId): array
    {
        try {
            return $this->get(
                $this->platformUrl() . "/lol/league/v4/entries/by-summoner/{$summonerId}",
                600
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
