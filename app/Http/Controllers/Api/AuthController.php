<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // ─── Register ─────────────────────────────────────────────────

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim'        => 'required|string|unique:users,nim',
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'password'   => 'required|string|min:6|confirmed',
            'phone'      => 'nullable|string|max:20',
            'angkatan'   => 'nullable|string|max:10',
            'jabatan'    => 'required|in:kahim,wakahim,sekum1,sekum2,bendum1,bendum2,kadiv,sekdiv,bendiv,anggota',
            'divisi'     => 'nullable|string|in:KWSB,Internal,Eksternal,Minbak,Sosma,Infokom,KWU',
            'sub_divisi' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nim'        => $request->nim,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'phone'      => $request->phone,
            'angkatan'   => $request->angkatan,
            'jabatan'    => $request->jabatan,
            'divisi'     => $request->divisi,
            'sub_divisi' => $request->sub_divisi,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Registrasi berhasil',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ], 201);
    }

    // ─── Login ────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $loginInput = $request->input('nim') ?? $request->input('email') ?? $request->input('login') ?? $request->input('username');

        if (!$loginInput || !$request->filled('password')) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => [
                    'nim'      => $loginInput ? [] : ['NIM atau Email wajib diisi.'],
                    'password' => $request->filled('password') ? [] : ['Password wajib diisi.'],
                ],
            ], 422);
        }

        $user = User::where('nim', $loginInput)
            ->orWhere('email', $loginInput)
            ->first();

        Log::info($request->all());
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'NIM/Email atau password salah',
            ], 401);
        }

        if ($user->status === 'nonaktif') {
            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ], 403);
        }

        // Hapus token lama agar tidak menumpuk
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;


        return response()->json([
            'message'      => 'Login berhasil',
            'token'        => $token,
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
            'role'         => $user->jabatan,
            'permissions'         => $user->permissions(),
            'is_admin' => $user->isAdmin()
        ]);
    }

    // ─── Logout ───────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    // ─── Me ───────────────────────────────────────────────────────

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user'         => $user,
            'role'         => $user->jabatan,
            'permissions'         => $user->permissions(),
            'is_admin' => $user->isAdmin()
        ]);
    }

    // ─── Refresh Token ────────────────────────────────────────────

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Token diperbarui',
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function getUserPermissions(User $user): array
    {
        return [
            'can_view_events'     => true,
            'can_create_event'    => $user->canCreateEvent(),
            'can_edit_event'      => $user->canEditEvent(),
            'can_delete_event'    => $user->canDeleteEvent(),
            'can_manage_users'    => $user->canManageUsers(),
            'can_manage_divisions' => $user->canManageDivisions(),
        ];
    }
}
