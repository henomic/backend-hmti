<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionWorkProgram;
use App\Models\DivisionHistory;
use App\Models\DivisionGallery;
use App\Models\DivisionMember;
use App\Models\roles_permisions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DivisionController extends Controller
{
    // ─── Index (Public) ───────────────────────────────────────────

    public function index(Request $request)
    {

        try {
            if ($request->id_user) {
                $user = User::find($request->id_user);
                $data = $user->CanManageDivisionsData(['workprogram'], ['R']) ?? [];

                $colectifdivisionId = collect($data)->pluck("permissionable_id")->values()->toArray();
                $colectifdivisionId[] = $user->division;
                $colectifdivisionId = array_unique($colectifdivisionId);
            }


            Log::info("function divison index sukses");
            $divisions = Division::with([
                'members.user',
                'workPrograms',
                "workPrograms.timetables",
                "workPrograms.timetables.documents",
                'histories',
                'galleries',
                'event',
                'event.timetables',
                'event.timetables.documents',

            ]);
            Log::info("id user nya");
            Log::info($request->all());
            if ($request->id_user && isset($user) && $user->isAdmin()) {
                $divisions->whereIn("visibility", ['publik', 'only_roles']);

                Log::info("id nya ni admin");
            }
            if ($request->id_user && !$user->isAdmin()) {
                # code...
                $divisions = $divisions->where("visibility", 'publik')->orWhere(function ($c) use ($colectifdivisionId) {
                    $c->whereIn("visibility", ['publik', 'only_roles'])->whereIn("id", $colectifdivisionId);
                });
            }
            if (!$request->id_user) {
                $divisions = $divisions->where("visibility", 'publik');
            }
            $divisions = $divisions->get();

            return response()->json($divisions->map(fn($d) => $this->formatDivision($d)));
        } catch (\Throwable $e) {

            Log::error('=== ADD MEMBER ERROR ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                // 'division_id' => $divisionId,
                'request' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan anggota',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_my_division(Request $request)
    {

        try {

            $user = auth()->user();

            $divisionId = [];


            foreach ($user->CanManageDivisionsData(['event', 'workprogram'], ['R']) as $value) {

                if (!in_array($value['management'], ['event', 'workprogram'])) {
                    continue;
                }

                $divisionId[] = $value['permissionable_id'];
                # code...


                Log::info("data no");
                Log::info($value['permissionable_id']);
                $management = $value['management'];

                $division_permission[$value['permissionable_id']][] = $management;
            }




            Log::info("data_permisionnya");
            Log::info($division_permission);
            Log::info($divisionId);

            // Log::info("function divison index sukses");
            $divisions = Division::with([
                'members.user',
                'histories',
                'galleries',
            ])
                ->whereIn('id', array_unique($divisionId))
                ->get()
                ->map(function ($d) use ($division_permission) {

                    $permission = $division_permission[$d->id] ?? [];

                    if (in_array('workprogram', $permission, true)) {
                        $d->load([
                            'workPrograms',
                            'workPrograms.timetables',
                            'workPrograms.timetables.documents',
                        ]);
                    }

                    if (in_array('event', $permission, true)) {
                        $d->load([
                            'event',
                            'event.timetables',
                            'event.timetables.documents',
                        ]);
                    }

                    return $this->formatDivision($d, $permission);
                });

            // Log::info("=== FINAL ===");
            // Log::info($divisions->toArray());



            return response()->json($divisions);
        } catch (\Throwable $e) {

            Log::error('=== ADD MEMBER ERROR ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                // 'division_id' => $divisionId,
                'request' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan anggota',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // ─── Show (Public) ────────────────────────────────────────────

    public function show($id)
    {
        $division = Division::with([
            'members.user',
            'workPrograms',
            'histories',
            'galleries',
        ])->findOrFail($id);

        return response()->json($this->formatDivision($division));
    }

    // ─── Update Division (Pimpinan only) ──────────────────────────

    public function update(Request $request, $id)
    {
        if (!auth()->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk mengubah divisi'], 403);
        }

        $division = Division::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|string|max:255',
            'full_name'        => 'sometimes|string|max:255',
            'color'            => 'sometimes|string|max:20',
            'color_light'      => 'sometimes|string|max:20',
            'color_soft'       => 'sometimes|string|max:20',
            'icon'             => 'sometimes|string|max:100',
            'description'      => 'sometimes|string',
            'vision'           => 'sometimes|string',
            'mission'          => 'sometimes|string',
            'established_year' => 'sometimes|integer|min:1900|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $division->update($request->only([
            'name',
            'full_name',
            'color',
            'color_light',
            'color_soft',
            'icon',
            'description',
            'vision',
            'mission',
            'established_year',
        ]));

        return response()->json([
            'message' => 'Divisi berhasil diperbarui',
            'data'    => $this->formatDivision($division->load(['members.user', 'workPrograms', 'histories', 'galleries'])),
        ]);
    }

    // ─── Add Work Program ─────────────────────────────────────────

    public function addWorkProgram(Request $request, $divisionId)
    {
        $division = Division::findOrFail($divisionId);

        // Kadiv hanya bisa tambah proker ke divisinya sendiri
        // $user = auth()->user();
        // if ($user->isKadiv() && $division->name !== $user->divisi) {
        //     return response()->json(['message' => 'Kadiv hanya dapat mengelola proker divisinya sendiri'], 403);
        // }
        // if (!$user->canManageEvent()) {
        //     return response()->json(['message' => 'Tidak memiliki izin'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'pic'      => 'required|string|max:100',
            'status'   => 'required|string|max:50',
            'progress' => 'sometimes|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $program = DivisionWorkProgram::create([
            'division_id' => $division->id,
            'name'        => $request->name,
            'pic'         => $request->pic,
            'status'      => $request->status,
            'progress'    => $request->progress ?? 0,
        ]);

        return response()->json([
            'message' => 'Program kerja berhasil ditambahkan',
            'data'    => $program,
        ], 201);
    }

    // ─── Add Gallery ──────────────────────────────────────────────

    public function addGallery(Request $request, $divisionId)
    {
        $division = Division::findOrFail($divisionId);

        $user = auth()->user();
        if ($user->isKadiv() && $division->name !== $user->divisi) {
            return response()->json(['message' => 'Kadiv hanya dapat mengelola galeri divisinya sendiri'], 403);
        }
        if (!$user->canManageEvent()) {
            return response()->json(['message' => 'Tidak memiliki izin'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'      => 'required|string|max:255',
            'icon'       => 'required|string|max:100',
            'bg_color'   => 'required|string|max:20',
            'image_path' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $gallery = DivisionGallery::create([
            'division_id' => $division->id,
            'title'       => $request->title,
            'icon'        => $request->icon,
            'bg_color'    => $request->bg_color,
            'image_path'  => $request->image_path,
        ]);

        return response()->json([
            'message' => 'Galeri berhasil ditambahkan',
            'data'    => $gallery,
        ], 201);
    }

    // ─── Add Division Member ─────────────────────────────────────

    public function addMember(Request $request, $divisionId)
    {
        Log::info('=== ADD MEMBER START ===');

        // Log parameter division
        Log::info('Division ID:', [
            'division_id' => $divisionId
        ]);

        // Log seluruh request
        Log::info('Request data:', $request->all());

        try {
            $division = Division::findOrFail($divisionId);

            Log::info('Division ditemukan:', [
                'division_id' => $division->id,
                'division_name' => $division->name ?? null,
            ]);

            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'position' => 'nullable|string|max:100',
                'batch'    => 'nullable|string|max:50',
                'email'    => 'nullable|email|max:255',
                'phone'    => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {

                Log::warning('Validasi addMember gagal:', [
                    'errors' => $validator->errors()->toArray(),
                    'request' => $request->all(),
                ]);

                return response()->json([
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Validasi berhasil');

            // Generate email
            $email = $request->email
                ?? (strtolower(str_replace(' ', '.', $request->name)) . '@hmti.or.id');

            Log::info('Email yang digunakan:', [
                'email' => $email
            ]);

            // Cari / buat user
            $user = \App\Models\User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $request->name,
                    'password' => bcrypt('password123'),
                    'role'     => 'user'
                ]
            );

            Log::info('User berhasil diproses:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created' => $user->wasRecentlyCreated,
            ]);

            // Buat member
            $member = DivisionMember::create([
                'division_id' => $division->id,
                'user_id'     => $user->id,
                'position'    => $request->position ?? 'Anggota',
                'batch'       => $request->batch ?? date('Y'),
                'email'       => $email,
                'phone'       => $request->phone,
            ]);

            Log::info('Division member berhasil dibuat:', [
                'member_id' => $member->id,
                'division_id' => $member->division_id,
                'user_id' => $member->user_id,
            ]);

            Log::info('=== ADD MEMBER SUCCESS ===');

            return response()->json([
                'message' => 'Anggota divisi berhasil ditambahkan',
                'data'    => [
                    'id'       => $member->id,
                    'name'     => $request->name,
                    'position' => $member->position,
                    'batch'    => $member->batch,
                    'email'    => $member->email,
                    'phone'    => $member->phone,
                ],
            ], 201);
        } catch (\Throwable $e) {

            Log::error('=== ADD MEMBER ERROR ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'division_id' => $divisionId,
                'request' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan anggota',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ─── Update Division Member ──────────────────────────────────

    public function updateMember(Request $request, $memberId)
    {
        $member = DivisionMember::with('user')->findOrFail($memberId);

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'position' => 'sometimes|nullable|string|max:100',
            'batch'    => 'sometimes|nullable|string|max:50',
            'email'    => 'sometimes|nullable|email|max:255',
            'phone'    => 'sometimes|nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $member->update($request->only(['position', 'batch', 'email', 'phone']));

        // Update name in user model if provided
        if ($request->filled('name') && $member->user) {
            $member->user->update(['name' => $request->name]);
        }

        return response()->json([
            'message' => 'Anggota divisi berhasil diperbarui',
            'data'    => $member->load('user'),
        ]);
    }

    // ─── Delete Division Member ──────────────────────────────────

    public function deleteMember($memberId)
    {
        $member = DivisionMember::findOrFail($memberId);
        $member->delete();

        return response()->json([
            'message' => 'Anggota divisi berhasil dihapus',
        ]);
    }

    // ─── Format Division Response ─────────────────────────────────

    private function formatDivision(
        Division $division,
        ?array $permission = null
    ): array {
        $permission = $permission ?? [];
        Log::info('FORMAT FUNCTION', [
            'division_id' => $division->id,
            'permission' => $permission,
            'has_event' => in_array('event', $permission, true),
            'has_workprogram' => in_array('workprogram', $permission, true),
        ]);
        $coordinator     = $division->getCoordinator();
        $viceCoordinator = $division->getViceCoordinator();

        $data = [
            'id'               => $division->id,
            'name'             => $division->name,
            'full_name'        => $division->full_name,
            'color'            => $division->color,
            'color_light'      => $division->color_light,
            'color_soft'       => $division->color_soft,
            'icon'             => $division->icon,
            'description'      => $division->description,
            'vision'           => $division->vision,
            'mission'          => $division->mission,
            'established_year' => $division->established_year,

            'coordinator' => $coordinator?->user?->name,

            'vice_coordinator' => $viceCoordinator?->user?->name,

            'members_count' => $division->members->count(),

            'members' => $division->members->map(fn($m) => [
                'id'       => $m->id,
                'name'     => $m->user?->name ?? 'Anggota Divisi',
                'position' => $m->position,
                'batch'    => $m->batch,
                'email'    => $m->email,
                'phone'    => $m->phone,
            ])->values(),

            'histories' => $division->histories->values(),

            'galleries' => $division->galleries->values(),

            'stats' => $division->getStats(),
        ];

        if (in_array('workprogram', $permission, true)) {
            Log::info("asup jing");
            $data['work_programs'] = $division->workPrograms->values();
        }

        if (in_array('event', $permission, true)) {
            $data['event'] = $division->event->values();
        }

        return $data;
    }
}
