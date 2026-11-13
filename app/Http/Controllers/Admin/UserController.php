<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use App\Support\MySqlAdvisoryLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()
                ->select(['id', 'name', 'email', 'role'])
                ->orderBy('name')
                ->paginate(15),
            'roles' => UserRole::cases(),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('updateRole', $user);

        $role = UserRole::from($request->validated('role'));
        $connection = DB::connection();
        $updateRole = fn (): User => $connection->transaction(function () use ($connection, $role, $user): User {
            $target = User::on($connection->getName())
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($target->isAdmin() && $role === UserRole::Member) {
                $adminIds = User::on($connection->getName())
                    ->where('role', UserRole::Admin->value)
                    ->lockForUpdate()
                    ->pluck('id');

                if ($adminIds->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'The last active Admin cannot be changed to Member.',
                    ]);
                }
            }

            $target->role = $role;
            $target->save();

            return $target;
        });

        if ($connection->getDriverName() === 'mysql') {
            app(MySqlAdvisoryLock::class)->synchronized(
                $connection,
                'taskflow:user-role-management',
                10,
                $updateRole,
            );
        } elseif ($connection->getDriverName() === 'sqlite' && app()->environment('testing')) {
            $updateRole();
        } else {
            throw new RuntimeException('User role management requires MySQL advisory locks outside the SQLite testing environment.');
        }

        return redirect()->route('admin.users.index')->with('status', 'User role updated successfully.');
    }
}
