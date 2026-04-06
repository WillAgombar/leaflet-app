<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignAreaRoutesRequest;
use App\Http\Requests\StoreCampaignRouteRequest;
use App\Http\Requests\UpdateCampaignRouteRequest;
use App\Models\CampaignRoute;
use App\Models\Campaigns;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CampaignRouteController extends Controller
{
    public function store(StoreCampaignRouteRequest $request, Campaigns $campaign): JsonResponse
    {
        $payload = $request->validated();
        $payload['campaign_id'] = $campaign->id;

        $campaignRoute = CampaignRoute::query()->create($payload);

        return response()->json([
            'message' => 'Template saved successfully.',
            'route' => [
                'id' => $campaignRoute->id,
                'name' => $campaignRoute->name,
                'route' => $campaignRoute->route_data,
            ],
        ], 201);
    }

    public function update(UpdateCampaignRouteRequest $request, Campaigns $campaign, CampaignRoute $campaignRoute): JsonResponse
    {
        if ($campaignRoute->campaign_id !== $campaign->id) {
            abort(404);
        }

        $payload = $request->validated();
        $payload['campaign_id'] = $campaign->id;

        $campaignRoute->update($payload);

        return response()->json([
            'message' => 'Route updated successfully.',
            'route' => [
                'id' => $campaignRoute->id,
                'name' => $campaignRoute->name,
                'route' => $campaignRoute->route_data,
            ],
        ]);
    }

    public function storeFromArea(StoreCampaignAreaRoutesRequest $request, Campaigns $campaign): JsonResponse
    {
        $name = trim($request->validated('name'));
        $points = $request->validated('points');
        $polygonString = $this->buildPolygonString($points);
        $polygonCoordinates = $this->buildPolygonCoordinates($points);
        $query = $this->buildOverpassQuery($polygonString);

        $overpassEndpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://overpass.nchc.org.tw/api/interpreter',
        ];

        $response = null;

        foreach ($overpassEndpoints as $endpoint) {
            $response = Http::timeout(25)
                ->retry(1, 500)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->withBody($query, 'text/plain')
                ->post($endpoint);

            if ($response->ok()) {
                break;
            }
        }

        if (!$response || !$response->ok()) {
            return response()->json([
                'message' => 'Unable to fetch roads for that area right now. Try again shortly.',
            ], 503);
        }

        $elements = $response->json('elements', []);
        $ways = collect($elements)
            ->filter(fn (array $element): bool => ($element['type'] ?? null) === 'way' && isset($element['geometry']))
            ->values();

        $maxRoutes = 120;
        $features = [];

        foreach ($ways->take($maxRoutes) as $way) {
            $lineFeatures = $this->buildLineFeatures($way, $polygonCoordinates);

            if (count($lineFeatures) === 0) {
                continue;
            }

            $features = array_merge($features, $lineFeatures);
        }

        if (count($features) === 0) {
            return response()->json([
                'message' => 'No roads found inside that area.',
            ], 422);
        }

        $routeData = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $campaignRoute = CampaignRoute::query()->create([
            'campaign_id' => $campaign->id,
            'name' => $name,
            'route_data' => $routeData,
        ]);

        return response()->json([
            'route' => [
                'id' => $campaignRoute->id,
                'name' => $campaignRoute->name,
                'route' => $campaignRoute->route_data,
            ],
            'total' => $ways->count(),
            'segments' => count($features),
        ], 201);
    }

    /**
     * @param array<int, array{lat: float, lng: float}> $points
     */
    protected function buildPolygonString(array $points): string
    {
        $polygonPoints = $points;

        if (count($polygonPoints) >= 3) {
            $first = $polygonPoints[0];
            $last = $polygonPoints[count($polygonPoints) - 1];

            if ($first['lat'] !== $last['lat'] || $first['lng'] !== $last['lng']) {
                $polygonPoints[] = $first;
            }
        }

        return collect($polygonPoints)
            ->map(fn (array $point): string => $point['lat'].' '.$point['lng'])
            ->implode(' ');
    }

    protected function buildOverpassQuery(string $polygonString): string
    {
        $highways = [
            'motorway',
            'trunk',
            'primary',
            'secondary',
            'tertiary',
            'unclassified',
            'residential',
            'service',
            'living_street',
            'road',
        ];
        $highwayFilter = implode('|', $highways);

        return trim(
            "[out:json][timeout:25];\n".
            "way[\"highway\"~\"^({$highwayFilter})$\"](poly:\"{$polygonString}\");\n".
            "(._;>;);\n".
            "out geom;"
        );
    }

    /**
     * @param array<string, mixed> $way
     * @param array<int, array{0: float, 1: float}> $polygonCoordinates
     * @return array<int, array<string, mixed>>
     */
    protected function buildLineFeatures(array $way, array $polygonCoordinates): array
    {
        $geometry = $way['geometry'] ?? null;

        if (!is_array($geometry) || count($geometry) < 2) {
            return [];
        }

        $coordinates = collect($geometry)
            ->filter(fn (array $point): bool => isset($point['lon'], $point['lat']))
            ->map(fn (array $point): array => [(float) $point['lon'], (float) $point['lat']])
            ->values()
            ->all();

        if (count($coordinates) < 2) {
            return [];
        }

        $segments = $this->clipLineStringToPolygon($coordinates, $polygonCoordinates);

        if (count($segments) === 0) {
            return [];
        }

        return collect($segments)
            ->filter(fn (array $segment): bool => count($segment) >= 2)
            ->map(fn (array $segment): array => [
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => $segment,
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $way
     */
    protected function formatRouteName(array $way): string
    {
        $tags = is_array($way['tags'] ?? null) ? $way['tags'] : [];
        $baseName = $tags['name'] ?? $tags['ref'] ?? $tags['highway'] ?? 'Road';
        $id = $way['id'] ?? '';
        $name = trim($baseName.' '.$id);

        return Str::limit($name, 120, '...');
    }

    /**
     * @param array<int, array{lat: float, lng: float}> $points
     * @return array<int, array{0: float, 1: float}>
     */
    protected function buildPolygonCoordinates(array $points): array
    {
        $polygon = collect($points)
            ->map(fn (array $point): array => [(float) $point['lng'], (float) $point['lat']])
            ->values()
            ->all();

        if (count($polygon) >= 3) {
            $first = $polygon[0];
            $last = $polygon[count($polygon) - 1];

            if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
                $polygon[] = $first;
            }
        }

        return $polygon;
    }

    /**
     * @param array{0: float, 1: float} $point
     * @param array<int, array{0: float, 1: float}> $polygon
     */
    protected function pointInPolygon(array $point, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        if ($count < 3) {
            return false;
        }

        $x = $point[0];
        $y = $point[1];

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $polygon[$i];
            [$xj, $yj] = $polygon[$j];

            $intersects = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * @param array{0: float, 1: float} $p1
     * @param array{0: float, 1: float} $p2
     * @param array{0: float, 1: float} $q1
     * @param array{0: float, 1: float} $q2
     * @return array{point: array{0: float, 1: float}, t: float}|null
     */
    protected function segmentIntersection(array $p1, array $p2, array $q1, array $q2): ?array
    {
        $x1 = $p1[0];
        $y1 = $p1[1];
        $x2 = $p2[0];
        $y2 = $p2[1];
        $x3 = $q1[0];
        $y3 = $q1[1];
        $x4 = $q2[0];
        $y4 = $q2[1];

        $den = ($x1 - $x2) * ($y3 - $y4) - ($y1 - $y2) * ($x3 - $x4);

        if (abs($den) < 1e-12) {
            return null;
        }

        $t = (($x1 - $x3) * ($y3 - $y4) - ($y1 - $y3) * ($x3 - $x4)) / $den;
        $u = (($x1 - $x3) * ($y1 - $y2) - ($y1 - $y3) * ($x1 - $x2)) / $den;

        if ($t < 0 || $t > 1 || $u < 0 || $u > 1) {
            return null;
        }

        return [
            'point' => [$x1 + $t * ($x2 - $x1), $y1 + $t * ($y2 - $y1)],
            't' => $t,
        ];
    }

    /**
     * @param array<int, array{0: float, 1: float}> $coordinates
     * @param array<int, array{0: float, 1: float}> $polygon
     * @return array<int, array<int, array{0: float, 1: float}>>
     */
    protected function clipLineStringToPolygon(array $coordinates, array $polygon): array
    {
        if (count($coordinates) < 2 || count($polygon) < 3) {
            return [];
        }

        $segments = [];
        $current = [];
        $previous = $coordinates[0];
        $previousInside = $this->pointInPolygon($previous, $polygon);

        if ($previousInside) {
            $current[] = $previous;
        }

        $edgeCount = count($polygon) - 1;

        for ($i = 1; $i < count($coordinates); $i++) {
            $next = $coordinates[$i];
            $intersections = [];
            $seen = [];

            for ($e = 0; $e < $edgeCount; $e++) {
                $hit = $this->segmentIntersection($previous, $next, $polygon[$e], $polygon[$e + 1]);

                if (!$hit) {
                    continue;
                }

                $point = $hit['point'];
                $key = round($point[0], 7).','.round($point[1], 7);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $intersections[] = $hit;
            }

            if (count($intersections) > 1) {
                usort($intersections, fn (array $a, array $b): int => $a['t'] <=> $b['t']);
            }

            if (count($intersections) === 0) {
                if ($previousInside) {
                    $current[] = $next;
                }
            } else {
                foreach ($intersections as $hit) {
                    $point = $hit['point'];

                    if ($previousInside) {
                        if (count($current) === 0) {
                            $current[] = $previous;
                        }

                        $current[] = $point;

                        if (count($current) >= 2) {
                            $segments[] = $current;
                        }

                        $current = [$point];
                    } else {
                        $current = [$point];
                    }

                    $previousInside = !$previousInside;
                }

                if ($previousInside) {
                    $current[] = $next;
                }
            }

            $previous = $next;
        }

        if (count($current) >= 2) {
            $segments[] = $current;
        }

        return $segments;
    }
}
