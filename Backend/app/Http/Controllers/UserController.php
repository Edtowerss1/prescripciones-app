<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UserFilterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * List users with optional role and search filters.
     *
     * GET /api/users
     */
    public function index(UserFilterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $limit = min((int) ($validated['limit'] ?? 15), 100);

        $users = User::with('doctor', 'patient')
            ->when(! empty($validated['role']), fn ($q) => $q->role($validated['role'], 'api'))
            ->when(! empty($validated['query']), function ($q) use ($validated) {
                $search = '%'.$validated['query'].'%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($limit);

        return UserResource::collection($users)->response();
    }

    /**
     * Create a new user with role and optional profile.
     *
     * POST /api/users
     */
    public function store(StoreUserRequest $request, UserService $svc): JsonResponse
    {
        $user = $svc->createUser($request->validated());

        $user->refresh();
        $user->load('doctor', 'patient');

        return response()->json(
            UserResource::make($user),
            201,
        );
    }
}
