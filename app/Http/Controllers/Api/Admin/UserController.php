<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /** GET /api/admin/users */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with(['services:id,key,name', 'almacenesConAcceso:id,nombre,codigo'])
            ->withCount('services');

        // Búsqueda por nombre o email
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtros
        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('service')) {
            $key = $request->string('service');
            $query->whereHas('services', fn ($q) => $q->where('key', $key));
        }

        // Incluir eliminados (soft delete)
        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $users = $query->orderBy('name')->paginate(min(100, $request->integer('per_page', 20)));

        return response()->json($users);
    }

    /** POST /api/admin/users */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        $user = new User;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password']; // cast 'hashed' aplica bcrypt
        $user->phone = $validated['phone'] ?? null;
        $user->role = $validated['role'];         // asignación explícita (no mass-assignable)
        $user->is_active = true;
        $user->save();

        AuditLog::record('user.created', "Creó al usuario {$user->name} ({$user->email})", $user);

        return response()->json($user->load('services'), 201);
    }

    /** GET /api/admin/users/{user} */
    public function show(User $user): JsonResponse
    {
        return response()->json(
            $user->load(['services', 'almacenesConAcceso:id,nombre,codigo'])
        );
    }

    /** PUT/PATCH /api/admin/users/{user} */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => ['sometimes', Rule::in(['admin', 'user'])],
        ]);

        // Protección: un admin no puede quitarse a sí mismo el rol admin.
        if (array_key_exists('role', $validated)
            && $user->id === $request->user()->id
            && $validated['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'No puedes quitarte a ti mismo el rol de administrador.',
            ]);
        }

        // Protección: no dejar el sistema sin ningún admin activo.
        if (array_key_exists('role', $validated)
            && $user->isAdmin()
            && $validated['role'] !== 'admin'
            && $this->esUltimoAdmin($user)) {
            throw ValidationException::withMessages([
                'role' => 'No puedes degradar al único administrador del sistema.',
            ]);
        }

        $user->fill($request->only(['name', 'email', 'phone']));
        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role']; // asignación explícita
        }
        $user->save();

        AuditLog::record('user.updated', "Editó al usuario {$user->name} ({$user->email})", $user, [
            'cambios' => array_keys($validated),
        ]);

        return response()->json($user->load('services'));
    }

    /** PATCH /api/admin/users/{user}/toggle-active */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        if ($user->is_active && $user->isAdmin() && $this->esUltimoAdmin($user)) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes desactivar al único administrador activo.',
            ]);
        }

        $user->is_active = ! $user->is_active; // asignación explícita
        $user->save();

        $accion = $user->is_active ? 'user.activated' : 'user.deactivated';
        $verbo = $user->is_active ? 'Activó' : 'Desactivó';
        AuditLog::record($accion, "{$verbo} al usuario {$user->name} ({$user->email})", $user);

        return response()->json(['is_active' => $user->is_active]);
    }

    /** POST /api/admin/users/{user}/reset-password */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->password = $validated['password'];      // cast 'hashed'
        $user->force_password_change = true;           // asignación explícita
        $user->setRememberToken(\Illuminate\Support\Str::random(60)); // invalida "recordarme"
        $user->save();

        AuditLog::record('user.password_reset', "Restableció la contraseña de {$user->name} ({$user->email})", $user);

        return response()->json(['message' => 'Contraseña restablecida. El usuario deberá cambiarla en su próximo ingreso.']);
    }

    /** DELETE /api/admin/users/{user} */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'user' => 'No puedes eliminar tu propia cuenta.',
            ]);
        }

        if ($user->isAdmin() && $this->esUltimoAdmin($user)) {
            throw ValidationException::withMessages([
                'user' => 'No puedes eliminar al único administrador del sistema.',
            ]);
        }

        $user->delete(); // soft delete

        AuditLog::record('user.deleted', "Eliminó al usuario {$user->name} ({$user->email})", $user);

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    /** POST /api/admin/users/{id}/restore */
    public function restore(string $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        AuditLog::record('user.restored', "Restauró al usuario {$user->name} ({$user->email})", $user);

        return response()->json(['message' => 'Usuario restaurado.']);
    }

    /** ¿Es el único admin activo del sistema? */
    private function esUltimoAdmin(User $user): bool
    {
        return User::where('role', 'admin')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
