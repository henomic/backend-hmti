<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\roles;
use App\Models\roles_permisions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class roleController extends Controller
{
    public function index(Request $request)
    {

        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }

        $roles = roles::withCount(["permision"])->get();
        return response()->json($roles);
    }


    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'is_admin' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'name.required' => 'Nama role wajib diisi.',
                'name.string' => 'Nama role harus berupa teks.',
                'name.max' => 'Nama role maksimal 100 karakter.',

                'is_admin.required' => 'Status admin wajib diisi.',
                'is_admin.boolean' => 'Status admin harus bernilai true atau false.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        /*
        |--------------------------------------------------------------------------
        | Normalisasi is_admin
        |--------------------------------------------------------------------------
        */

        $data['is_admin'] = (bool) $data['is_admin'];

        /*
        |--------------------------------------------------------------------------
        | Cek duplicate nama role
        |--------------------------------------------------------------------------
        */

        $existingRole = roles::whereRaw(
            'LOWER(name) = ?',
            [strtolower(trim($data['name']))]
        )->first();

        if ($existingRole) {
            return response()->json([
                'success' => false,
                'message' => 'Nama role sudah digunakan.',
                'errors' => [
                    'name' => [
                        'Nama role sudah digunakan.'
                    ]
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        $role = roles::create([
            'name' => trim($data['name']),
            'is_admin' => $data['is_admin'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dibuat.',
            'data' => $role,
        ], 201);
    }

    /**
     * GET /api/roles/{id}
     */
    public function show($id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $role = roles::with('permissions')->find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data role berhasil diambil.',
            'data' => $role,
        ], 200);
    }

    /**
     * PUT /api/roles/{id}
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $role = roles::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'is_admin' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'name.required' => 'Nama role wajib diisi.',
                'name.string' => 'Nama role harus berupa teks.',
                'name.max' => 'Nama role maksimal 100 karakter.',

                'is_admin.required' => 'Status admin wajib diisi.',
                'is_admin.boolean' => 'Status admin harus bernilai true atau false.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        /*
        |--------------------------------------------------------------------------
        | Normalisasi
        |--------------------------------------------------------------------------
        */

        $data['name'] = trim($data['name']);
        $data['is_admin'] = (bool) $data['is_admin'];

        /*
        |--------------------------------------------------------------------------
        | Cek duplicate nama role
        |--------------------------------------------------------------------------
        */

        $existingRole = roles::whereRaw(
            'LOWER(name) = ?',
            [strtolower($data['name'])]
        )
            ->where('id', '!=', $role->id)
            ->first();

        if ($existingRole) {
            return response()->json([
                'success' => false,
                'message' => 'Nama role sudah digunakan.',
                'errors' => [
                    'name' => [
                        'Nama role sudah digunakan.'
                    ]
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $role->update([
            'name' => $data['name'],
            'is_admin' => $data['is_admin'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diperbarui.',
            'data' => $role->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/roles/{id}
     */
    public function destroy($id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $role = roles::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Cek apakah role masih digunakan oleh permission
        |--------------------------------------------------------------------------
        */

        if ($role->permision()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak dapat dihapus karena masih memiliki permission.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus.',
        ], 200);
    }
}
