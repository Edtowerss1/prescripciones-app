<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List users with optional role and search filters.
     *
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 15), 100);

        $users = User::with('doctor', 'patient')
            ->when($request->filled('role'), fn ($q) => $q->role($request->query('role'), 'api'))
            ->when($request->filled('query'), function ($q) use ($request) {
                $search = '%'.$request->query('query').'%';
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

        return UserResource::make($user)->response()->setStatusCode(201);
    }
}
