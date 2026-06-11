<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class LoLEsportsService
{
    private const BASE_URL  = 'https://esports-api.lolesports.com/persisted/gw';
    private const FEED_URL  = 'https://feed.lolesports.com/livestats/v1';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $apiKey,
    ) {}

    private function get(string $url, array $query = [], int $ttl = 300): array
    {
        $cacheKey = 'esports_' . md5($url . serialize($query));
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($url, $query, $ttl) {
            $item->expiresAfter($ttl);
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['x-api-key' => $this->apiKey],
                'query'   => array_merge(['hl' => 'fr-FR'], $query),
            ]);
            return $response->toArray();
        });
    }

    // ── Leagues ───────────────────────────────────────────────────────────────

    public function getLeagues(): array
    {
        try {
            $data = $this->get(self::BASE_URL . '/getLeagues', [], 86400);
            return $data['data']['leagues'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Tournaments ───────────────────────────────────────────────────────────

    public function getTournamentsForLeague(string $leagueId): array
    {
        try {
            $data = $this->get(self::BASE_URL . '/getTournamentsForLeague', ['leagueId' => $leagueId], 3600);
            return $data['data']['leagues'][0]['tournaments'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Schedule ──────────────────────────────────────────────────────────────

    public function getSchedule(?string $leagueId = null): array
    {
        try {
            $query = $leagueId ? ['leagueId' => $leagueId] : [];
            $data  = $this->get(self::BASE_URL . '/getSchedule', $query, 300);
            return $data['data']['schedule']['events'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Return only upcoming unstarted matches.
     */
    public function getUpcomingMatches(?string $leagueId = null): array
    {
        $events = $this->getSchedule($leagueId);
        return array_filter($events, fn($e) => ($e['state'] ?? '') === 'unstarted');
    }

    /**
     * Convert a LoL Esports schedule event into SportEvent-friendly data.
     */
    public function buildEventData(array $esportsEvent): array
    {
        $match     = $esportsEvent['match'] ?? [];
        $teams     = $match['teams'] ?? [];
        $teamA     = $teams[0] ?? [];
        $teamB     = $teams[1] ?? [];
        $startTime = $esportsEvent['startTime'] ?? 'now';

        return [
            'name'        => ($teamA['name'] ?? '?') . ' vs ' . ($teamB['name'] ?? '?'),
            'tournament'  => $esportsEvent['league']['name'] ?? 'LoL Esports',
            'teamA'       => $teamA['name'] ?? 'Équipe A',
            'teamB'       => $teamB['name'] ?? 'Équipe B',
            'teamACode'   => $teamA['code'] ?? '',
            'teamBCode'   => $teamB['code'] ?? '',
            'teamAImage'  => $teamA['image'] ?? null,
            'teamBImage'  => $teamB['image'] ?? null,
            'startsAt'    => new \DateTime($startTime),
            'source'      => 'lol_esports',
            'matchId'     => $match['id'] ?? null,
        ];
    }

    // ── Live / standings ──────────────────────────────────────────────────────

    public function getLiveMatches(): array
    {
        try {
            $data = $this->get(self::BASE_URL . '/getLive', [], 30);
            return $data['data']['schedule']['events'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function getStandings(string $tournamentId): array
    {
        try {
            $data = $this->get(self::BASE_URL . '/getStandings', ['tournamentId' => $tournamentId], 3600);
            return $data['data']['standings'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ── Teams ─────────────────────────────────────────────────────────────────

    public function getTeams(): array
    {
        try {
            $data = $this->get(self::BASE_URL . '/getTeams', [], 86400);
            return $data['data']['teams'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }
}
