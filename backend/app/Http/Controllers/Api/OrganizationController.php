<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizations = $request->user()
            ->organizations()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['organizations' => $organizations]);
    }

    public function store(Request $request, OrganizationService $organizations): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'regex:/yandex\.(ru|com)\/maps/'],
        ]);

        $organization = $organizations->import($request->user(), $validated['url']);

        return response()->json(['organization' => $organization], 201);
    }

    public function refresh(Organization $organization, OrganizationService $organizations): JsonResponse
    {
        $this->ensureOwner($organization);

        $organization = $organizations->refresh($organization);

        return response()->json(['organization' => $organization]);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $this->ensureOwner($organization);

        $organization->delete();

        return response()->json(['message' => 'Организация удалена.']);
    }

    public function reviews(Request $request, Organization $organization): JsonResponse
    {
        $this->ensureOwner($organization);

        $reviews = $organization->reviews()
            ->orderByDesc('published_at')
            ->paginate(50);

        return response()->json($reviews);
    }

    private function ensureOwner(Organization $organization): void
    {
        if ($organization->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
