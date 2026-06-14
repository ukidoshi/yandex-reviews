<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class OrganizationService
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private const MAX_PAGES = 14; // 14 × 50 = 700, больше API не отдаёт

    public function import(User $user, string $url): Organization
    {
        $data = $this->fetchFromYandex($url);

        $exists = Organization::query()
            ->where('user_id', $user->id)
            ->where('yandex_org_id', $data['org_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'url' => ['Эта организация уже добавлена.'],
            ]);
        }

        return $this->save($user, $url, $data);
    }

    public function refresh(Organization $organization): Organization
    {
        $data = $this->fetchFromYandex($organization->source_url);

        return $this->save($organization->user, $organization->source_url, $data, $organization);
    }

    private function save(User $user, string $url, array $data, ?Organization $organization = null): Organization
    {
        return DB::transaction(function () use ($user, $url, $data, $organization) {
            $organization ??= new Organization(['user_id' => $user->id]);

            $organization->fill([
                'yandex_org_id' => $data['org_id'],
                'name' => $data['name'] ?? 'Без названия',
                'source_url' => $url,
                'average_rating' => $data['average_rating'],
                'ratings_count' => $data['ratings_count'] ?? 0,
                'reviews_count' => $data['reviews_count'] ?? 0,
                'synced_at' => now(),
            ])->save();

            $organization->reviews()->delete();

            foreach ($data['reviews'] as $review) {
                if (empty($review['review_id'])) {
                    continue;
                }

                $organization->reviews()->create([
                    'yandex_review_id' => $review['review_id'],
                    'author_name' => $review['author_name'],
                    'published_at' => $review['date'],
                    'text' => $review['text'],
                    'rating' => $review['rating'],
                ]);
            }

            return $organization->fresh();
        });
    }

    private function fetchFromYandex(string $url): array
    {
        $orgId = $this->extractOrgId($url);
        $cookieJar = new CookieJar;
        $session = $this->initSession($orgId, $cookieJar);
        $reviews = [];
        $page = 1;
        $totalPages = 1;

        do {
            $response = $this->requestReviewsPage($orgId, $session, $cookieJar, $page);

            if ($response === null) {
                if ($page === 1 && $session['meta']['reviews_count'] > 0) {
                    throw new RuntimeException('Яндекс API не вернул отзывы.');
                }

                break;
            }

            $pageReviews = $response['data']['reviews'] ?? [];
            $reviews = array_merge($reviews, $pageReviews);
            $totalPages = (int) ($response['data']['params']['totalPages'] ?? $page);

            if ($page === self::MAX_PAGES || $pageReviews === []) {
                break;
            }

            $page++;
        } while ($page <= $totalPages);

        $reviews = array_slice(array_map([$this, 'normalizeReview'], $reviews), 0, self::MAX_PAGES * 50);

        return [
            'org_id' => $orgId,
            'name' => $session['meta']['name'],
            'average_rating' => $session['meta']['average_rating'],
            'ratings_count' => $session['meta']['ratings_count'],
            'reviews_count' => $session['meta']['reviews_count'],
            'reviews' => $reviews,
        ];
    }

    private function extractOrgId(string $url): string
    {
        if (preg_match('#yandex\.(?:ru|com)/maps/org/[^/]+/(\d+)#i', $url, $matches)) {
            return $matches[1];
        }

        $decodedUrl = urldecode(html_entity_decode($url));

        if (preg_match('#oid=(\d+)#', $decodedUrl, $matches)) {
            return $matches[1];
        }

        throw new InvalidArgumentException('Не удалось извлечь ID организации из ссылки Яндекс Карт.');
    }

    private function initSession(string $orgId, CookieJar $cookieJar): array
    {
        $reviewsUrl = sprintf('https://yandex.ru/maps/org/%s/reviews/', $orgId);

        $response = $this->httpClient($cookieJar, $reviewsUrl)->get($reviewsUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Не удалось загрузить страницу организации на Яндекс Картах.');
        }

        $reviewsUrl = (string) $response->effectiveUri();
        $state = $this->extractPageState($response->body());

        $item = $state['stack'][0]['results']['items'][0] ?? [];
        $ratingData = $item['ratingData'] ?? [];

        return [
            'csrf_token' => $state['config']['csrfToken'] ?? null,
            'session_id' => $state['config']['counters']['analytics']['sessionId'] ?? null,
            'req_id' => $state['stack'][0]['results']['requestId'] ?? null,
            'reviews_url' => $reviewsUrl,
            'meta' => [
                'name' => $item['title'] ?? null,
                'average_rating' => $ratingData['ratingValue'] ?? null,
                'ratings_count' => (int) ($ratingData['ratingCount'] ?? 0),
                'reviews_count' => (int) ($ratingData['reviewCount'] ?? 0),
            ],
        ];
    }

    private function requestReviewsPage(string $orgId, array $session, CookieJar $cookieJar, int $page): ?array
    {
        $query = [
            'ajax' => '1',
            'csrfToken' => $session['csrf_token'],
            'sessionId' => $session['session_id'],
            'businessId' => $orgId,
            'locale' => 'ru_RU',
            'page' => $page,
            'pageSize' => 50,
            'ranking' => 'by_relevance_org',
            'reqId' => $session['req_id'],
        ];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $response = $this->sendSignedRequest($query, $session['reviews_url'], $cookieJar);

            if (isset($response['data']['reviews'])) {
                return $response;
            }

            if (isset($response['csrfToken'])) {
                $query['csrfToken'] = $response['csrfToken'];
            }
        }

        return null;
    }

    private function sendSignedRequest(array $query, string $reviewUrl, CookieJar $cookieJar): array
    {
        $query['s'] = $this->signRequest($query);

        $response = $this->httpClient($cookieJar, $reviewUrl)
            ->get('https://yandex.ru/maps/api/business/fetchReviews', $query);

        if (! $response->successful()) {
            throw new RuntimeException('Ошибка запроса к API отзывов Яндекс Карт.');
        }

        return $response->json() ?? [];
    }

    private function httpClient(CookieJar $cookieJar, string $reviewUrl)
    {
        return Http::withOptions(['cookies' => $cookieJar])
            ->withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'ru-RU,ru;q=0.9',
                'Referer' => $reviewUrl,
                'Origin' => 'https://yandex.ru',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);
    }

    private function signRequest(array $query): string
    {
        unset($query['s']);

        $query = array_filter($query, static fn ($value) => $value !== null && $value !== '');
        uksort($query, static fn (string $left, string $right): int => strcasecmp($left, $right));

        $parts = [];

        foreach ($query as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $parts[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        return (string) $this->hashSignString(implode('&', $parts));
    }

    private function hashSignString(string $value): int
    {
        $hash = 5381;

        for ($index = 0, $length = strlen($value); $index < $length; $index++) {
            $hash = ((33 * $hash) ^ ord($value[$index])) & 0xFFFFFFFF;
        }

        return $hash;
    }

    private function extractPageState(string $html): array
    {
        if (! preg_match('/<script[^>]*type="application\/json"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            throw new InvalidArgumentException('Не удалось прочитать состояние страницы Яндекс Карт.');
        }

        $state = json_decode($matches[1], true);

        if (! is_array($state)) {
            throw new InvalidArgumentException('Некорректное состояние страницы Яндекс Карт.');
        }

        return $state;
    }

    private function normalizeReview(array $review): array
    {
        $author = $review['author'] ?? [];

        return [
            'review_id' => $review['reviewId'] ?? null,
            'author_name' => $author['name'] ?? null,
            'text' => $review['text'] ?? null,
            'rating' => $review['rating'] ?? null,
            'date' => $review['updatedTime'] ?? null,
        ];
    }
}
