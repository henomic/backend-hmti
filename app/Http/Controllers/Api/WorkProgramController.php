<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionWorkProgram;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WorkProgramController extends Controller
{
    // ─── Index (Public) ───────────────────────────────────────────

    public function index(Request $request)
    {
        $query = DivisionWorkProgram::with('division', 'timetables');
        if ($request->id_user) {
            $user = User::select('id', 'jabatan', 'division')->find($request->id_user);
            $data = $user->CanManageDivisionsData(['workprogram'], ['R']) ?? [];

            $colectifdivisionId = collect($data)->pluck("permissionable_id")->values()->toArray();
            $colectifdivisionId[] = $user->division;
            $colectifdivisionId = array_unique($colectifdivisionId);
            Log::info("user nya nih");
            Log::info($colectifdivisionId);
        }

        // Filter by division (ID or Name)
        if ($request->filled('division') && $request->division !== 'all') {
            $divisionParam = $request->division;
            $query->whereHas('division', function ($q) use ($divisionParam) {
                if (is_numeric($divisionParam)) {
                    $q->where('id', $divisionParam);
                } else {
                    $q->where('name', $divisionParam)
                        ->orWhere('full_name', 'LIKE', "%{$divisionParam}%");
                }
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by name, pic, or division name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    //   ->orWhere('pic', 'LIKE', "%{$search}%")
                    ->orWhereHas('division', function ($dq) use ($search) {
                        $dq->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('full_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if (!isset($request->id_user)) {

            $query->where('allowed', 'publik')->whereHas('division', function ($d) {
                $d->where("visibility", 'publik');
            });
        } else {

            $query->where(function ($q) use ($colectifdivisionId) {

                $q->whereIn('allowed', ['anggota', 'publik'])

                    ->orWhere(function ($q) use ($colectifdivisionId) {

                        $q->where('allowed', 'divisi')
                            ->whereHas('division', function ($d) use ($colectifdivisionId) {
                                $d->whereIn('id', $colectifdivisionId);
                            });
                    });
            })->whereHas('division', function ($d) use ($colectifdivisionId) {
                $d->where("visibility", 'publik')->orWhere(function ($d) use ($colectifdivisionId) {
                    $d->where("visibility", 'only_roles')->whereIn('id', $colectifdivisionId);
                });
            });
        }

        $programs = $query
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($programs->map(fn($p) => $this->formatWorkProgram($p)));
    }

    // ─── Show (Public) ────────────────────────────────────────────

    public function show($id)
    {
        $program = DivisionWorkProgram::with('division')->findOrFail($id);

        return response()->json($this->formatWorkProgram($program));
    }

    // ─── Get By Division (Public) ──────────────────────────────────

    public function getByDivision(Request $request, $divisionId)
    {
        if (is_numeric($divisionId)) {
            $division = Division::findOrFail($divisionId);
        } else {
            $division = Division::where('name', $divisionId)
                ->orWhere('full_name', $divisionId)
                ->firstOrFail();
        }

        $query = DivisionWorkProgram::with('division')
            ->where('division_id', $division->id);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
                //   ->orWhere('pic', 'LIKE', "%{$search}%")
            });
        }

        $programs = $query->orderBy('id', 'asc')->get();

        return response()->json($programs->map(fn($p) => $this->formatWorkProgram($p)));
    }

    // ─── Update (Protected) ───────────────────────────────────────

    public function update(Request $request, $id)
    {
        $program = DivisionWorkProgram::with('division')->findOrFail($id);
        $user = auth()->user();

        // if ($user->isKadiv() && $program->division?->name !== $user->divisi) {
        //     return response()->json(['message' => 'Kadiv hanya dapat mengoperasikan proker divisinya sendiri'], 403);
        // }

        // if (!$user->canManageEvent()) {
        //     return response()->json(['message' => 'Tidak memiliki izin untuk mengedit proker'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            // 'date'     => 'sometimes|string|max:50',
            'allowed'      => 'sometimes|string|max:100',
            'status'   => 'sometimes|string|max:50',
            'progress' => 'sometimes|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $program->update($request->only(['name', 'allowed',   'status', 'progress']));
        // 'pic'
        return response()->json([
            'message' => 'Program kerja berhasil diperbarui',
            'data'    => $this->formatWorkProgram($program->fresh('division')),
        ]);
    }

    // ─── Destroy (Protected) ──────────────────────────────────────

    public function destroy($id)
    {
        $program = DivisionWorkProgram::with('division')->findOrFail($id);
        $user = auth()->user();

        if ($user->isKadiv() && $program->division?->name !== $user->divisi) {
            return response()->json(['message' => 'Kadiv hanya dapat menghapus proker divisinya sendiri'], 403);
        }

        if (!$user->canManageEvent()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk menghapus proker'], 403);
        }

        $program->delete();

        return response()->json(['message' => 'Program kerja berhasil dihapus']);
    }

    // ─── Helper Format ────────────────────────────────────────────

    private function formatWorkProgram(DivisionWorkProgram $program): array
    {
        return [
            'id'                 => $program->id,
            'division_id'        => $program->division_id,
            'division_name'      => $program->division?->name,
            'division_full_name' => $program->division?->full_name,
            'division_color'     => $program->division?->color,
            'division_icon'      => $program->division?->icon,
            'name'               => $program->name,
            // 'date'               => $program->date,
            // 'pic'                => $program->pic,
            'status'             => $program->status,
            'progress'           => (int) $program->progress,
            'created_at'         => $program->created_at?->toISOString(),
            "timetables"         => $program->timetables,
            'updated_at'         => $program->updated_at?->toISOString(),
        ];
    }
}
