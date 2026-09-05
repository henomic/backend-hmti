<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\roles_permisions;
use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class rolePermisionController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $roles_permision =        roles_permisions::with(['roles'])->get();

        return response()->json($roles_permision);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        try {
            $validator = FacadesValidator::make($request->all(), [
                'role_id' => [
                    'required',
                    'integer',
                    'exists:roles,id',
                ],

                'management' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'jenis' => [
                    'required',
                    'in:division,costum',
                ],

                'permissionable_type' => [
                    'nullable',
                    'string',
                ],

                'permissionable_id' => [
                    'nullable',
                    'integer',
                ],

                'actions' => [
                    'required',
                    'string',
                    'max:20',
                ],
            ]);

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
    | Handle permissionable
    |--------------------------------------------------------------------------
    */

            if ($data['jenis'] === 'division') {

                $data['permissionable_type'] = null;
                $data['permissionable_id'] = null;
            } else {

                if (empty($data['permissionable_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Division wajib dipilih untuk jenis costum.',
                    ], 422);
                }

                $data['permissionable_type'] = 'App\\Models\\Division';
            }

            /*
    |--------------------------------------------------------------------------
    | Handle actions
    |--------------------------------------------------------------------------
    */

            $allowedActions = ['C', 'R', 'U', 'D'];

            $actions = collect(explode(',', $data['actions']))
                ->map(function ($action) {
                    return strtoupper(trim($action));
                })
                ->filter(function ($action) use ($allowedActions) {
                    return in_array($action, $allowedActions);
                })
                ->unique()
                ->values()
                ->implode(',');

            if ($actions === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal satu action harus dipilih.',
                ], 422);
            }

            $data['actions'] = $actions;

            /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

            $permission = roles_permisions::create($data);

            $permission->load([
                'roles',
                'permissionable',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil dibuat.',
                'data' => $permission,
            ], 201);
        } catch (\Throwable $th) {
            Log::info("error di insert role permision");
            Log::info($th->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $permission = roles_permisions::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        $validator = FacadesValidator::make($request->all(), [
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],

            'management' => [
                'required',
                'string',
                'max:100',
            ],

            'jenis' => [
                'required',
                'in:division,costum',
            ],

            'permissionable_type' => [
                'nullable',
                'string',
            ],

            'permissionable_id' => [
                'nullable',
                'integer',
            ],

            'actions' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

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
    | Handle permissionable
    |--------------------------------------------------------------------------
    */

        if ($data['jenis'] === 'division') {

            $data['permissionable_type'] = null;
            $data['permissionable_id'] = null;
        } else {

            if (empty($data['permissionable_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Division wajib dipilih untuk jenis costum.',
                ], 422);
            }

            $data['permissionable_type'] = 'App\\Models\\Division';
        }

        /*
    |--------------------------------------------------------------------------
    | Handle actions
    |--------------------------------------------------------------------------
    */

        $allowedActions = ['C', 'R', 'U', 'D'];

        $actions = collect(explode(',', $data['actions']))
            ->map(function ($action) {
                return strtoupper(trim($action));
            })
            ->filter(function ($action) use ($allowedActions) {
                return in_array($action, $allowedActions);
            })
            ->unique()
            ->values()
            ->implode(',');

        if ($actions === '') {
            return response()->json([
                'success' => false,
                'message' => 'Minimal satu action harus dipilih.',
            ], 422);
        }

        $data['actions'] = $actions;

        /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

        $permission->update($data);

        $permission->load([
            'roles',
            'permissionable',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil diperbarui.',
            'data' => $permission,
        ]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $permission = roles_permisions::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil dihapus.',
        ]);
    }
    public function show($id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        }
        $permission = roles_permisions::with([
            'roles',
            'permissionable',
        ])->find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $permission,
        ]);
    }
}
