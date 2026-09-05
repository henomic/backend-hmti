<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Api\WorkProgramController;
use App\Http\Controllers\Controller;
use App\Models\DivisionWorkProgram;
use App\Models\Event;
use App\Models\timetable;
use App\Models\User;
use Dotenv\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class TimeableController extends Controller
{

    public function index(Request $request)
    {
        try {

            if ($request->id_user) {
                $user = User::select('id', 'jabatan', 'division')->find($request->id_user);
                $data = $user->CanManageDivisionsData(['workprogram', 'event'], ['R']) ?? [];

                $colectifdivisionId = collect($data)->pluck("permissionable_id")->values()->toArray();
                $colectifdivisionId[] = $user->division;
                $colectifdivisionId = array_unique($colectifdivisionId);
            }

            $tb = timetable::with(['documents', 'timetables', 'timetables.division']);
            if (!isset($request->id_user)) {

                $tb->where('status', 'publik')
                    ->whereHasMorph(
                        'timetables',
                        [
                            Event::class,
                            DivisionWorkProgram::class,
                        ],
                        function ($q) {
                            $q->whereHas('division', function ($division) {
                                $division->where('visibility', 'publik');
                            });

                            $q->where('allowed', 'publik');
                        }
                    );

                Log::info('user tidak login ya memek');
            }

            if (isset($request->id_user)) {

                $tb->where(function ($q) use ($colectifdivisionId) {


                    $q->where('status', 'publik')
                        ->orWhere('status', 'anggota')
                        ->orWhere(function ($status) use ($colectifdivisionId) {
                            $status->where('status', 'divisi')
                                ->whereHasMorph(
                                    'timetables',
                                    [
                                        Event::class,
                                        DivisionWorkProgram::class,
                                    ],
                                    function ($resource) use ($colectifdivisionId) {

                                        $resource->whereIn(
                                            'division_id',
                                            $colectifdivisionId
                                        );
                                    }
                                );
                        });
                });
                // =============================================================
                // RESOURCE ACCESS
                // EVENT / WORK PROGRAM
                // =============================================================

                $tb->whereHasMorph(
                    'timetables',
                    [
                        Event::class,
                        DivisionWorkProgram::class,
                    ],
                    function ($resource) use ($colectifdivisionId) {

                        // =====================================================
                        // DIVISION VISIBILITY
                        // =====================================================

                        $resource->whereHas('division', function ($division) use ($colectifdivisionId) {

                            $division->where(function ($v) use ($colectifdivisionId) {

                                // Divisi publik
                                $v->where('visibility', 'publik')

                                    // Divisi only_roles
                                    ->orWhere(function ($r) use ($colectifdivisionId) {

                                        $r->where('visibility', 'only_roles')
                                            ->whereIn(
                                                'id',
                                                $colectifdivisionId
                                            );
                                    });
                            });
                        });


                        // =====================================================
                        // ALLOWED RESOURCE
                        // =====================================================

                        $resource->where(function ($allowed) use ($colectifdivisionId) {

                            // Publik
                            $allowed->whereIn(
                                'allowed',
                                [
                                    'publik',
                                    'anggota',
                                ]
                            )

                                // Khusus divisi
                                ->orWhere(function ($divisi) use ($colectifdivisionId) {

                                    $divisi->where('allowed', 'divisi')
                                        ->whereIn(
                                            'division_id',
                                            $colectifdivisionId
                                        );
                                });
                        });
                    }
                );

                Log::info('user login ya memek');
            }

            $tb = $tb->get();

            return response()->json([
                "data" => $tb->map(fn($tm) => $this->formatTm($tm))
            ]);
            //code...
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()]);
        }
    }

    // public function index(Request $request)
    // {
    //     $user = $request->user();

    //     $timetables = timetable::query()
    //         ->with(['documents', 'timetables', 'timetables.division'])
    //         ->where(function ($query) use ($user) {

    //             // 1. DATA PUBLIK
    //             $query->where('status', 'publik');

    //             // 2. DATA ANGGOTA
    //             if ($user) {
    //                 $query->orWhere('status', 'anggota');
    //             }

    //             // 3. DATA DIVISI
    //             if ($user) {
    //                 $query->orWhere(function ($query) use ($user) {

    //                     $query->where('status', 'divisi')
    //                         ->whereHasMorph(
    //                             'timetables',
    //                             [
    //                                 Event::class,
    //                                 DivisionWorkProgram::class,
    //                             ],
    //                             function ($query) use ($user) {
    //                                 $query->where('division_id', $user->division_id);
    //                             }
    //                         );
    //                 });
    //             }
    //         })
    //         ->get();

    //     return response()->json([
    //         'data' => $timetables,
    //     ]);
    // }


    public function store(Request $request)
    {



        try {

            Log::info("data dari timetable store");
            Log::info($request->all());
            $validator = FacadesValidator::make($request->all(), ['source_id' => 'required|integer', 'source_type' => 'required', 'location' => 'required|string', 'status' => 'required|string', 'start_time' => 'required|date', 'end_time' => 'nullable|date|after_or_equal:start_time',]);
            if ($validator->fails()) {
                Log::info("validasi gagal store timetable");
                Log::info($request->all());
                return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors(),], 422);
            } /* |-------------------------------------------------------------------------- | Tentukan source berdasarkan source_type |-------------------------------------------------------------------------- */
            if ($request->source_type === 'Agenda') {
                $source = Event::find($request->source_id);
            } elseif ($request->source_type === 'Proker') {
                $source = DivisionWorkProgram::find($request->source_id);
            } else {
                return response()->json(['message' => 'Source type tidak sesuai', 'source_type' => $request->source_type,], 422);
            } /* |-------------------------------------------------------------------------- | Source tidak ditemukan |-------------------------------------------------------------------------- */
            if (!$source) {
                return response()->json(['message' => 'Source dengan ID tersebut tidak ditemukan', 'source_id' => $request->source_id, 'source_type' => $request->source_type,], 404);
            } /* |-------------------------------------------------------------------------- | Create Timetable melalui morph relation |-------------------------------------------------------------------------- */
            $timetable = $source->timetables()->create(['location' => $request->location, 'status' => $request->status, 'start_time' => $request->start_time, 'end_time' => $request->end_time,]);
            return response()->json(['message' => 'Timetable berhasil dibuat', 'data' => $timetable,], 201);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat timetable', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => 'Terjadi kesalahan saat membuat timetable', 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),], 500);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            $validator = FacadesValidator::make($request->all(), ['location' => 'required|string', 'status' => 'required|string', 'start_time' => 'required|date', 'end_time' => 'nullable|date',]);
            if ($validator->fails()) {
                return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors(),], 422);
            } /* |-------------------------------------------------------------------------- | Cari timetable |-------------------------------------------------------------------------- */
            $timetable = timetable::find($id);
            if (!$timetable) {
                return response()->json(['message' => 'Timetable tidak ditemukan', 'id' => $id,], 404);
            } /* |-------------------------------------------------------------------------- | Update data |-------------------------------------------------------------------------- */
            $timetable->update(['location' => $request->location, 'status' => $request->status, 'start_time' => $request->start_time, 'end_time' => $request->end_time,]);
            return response()->json(['message' => 'Timetable berhasil diperbarui', 'data' => $timetable->fresh(),], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal update timetable', ['id' => $id, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => 'Terjadi kesalahan saat update timetable', 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),], 500);
        }
    }

    public function delete($id)
    {
        try {
            $timetable = timetable::find($id);
            if (!$timetable) {
                return response()->json(['message' => 'Timetable tidak ditemukan', 'id' => $id,], 404);
            }
            $timetable->delete();
            return response()->json(['message' => 'Timetable berhasil dihapus',], 200);
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus timetable', ['id' => $id, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),]);
            return response()->json(['message' => 'Terjadi kesalahan saat menghapus timetable', 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),], 500);
        }
    }

    private function formatTm(timetable $tm): array
    {
        $type = $tm->timetables_type === Event::class
            ? $tm->timetables->type_event
            : 'proker';

        $data = $tm->timetables;
        Log::info("documents nya");
        Log::info($tm->documents);
        Log::info("DATA SEMUA NYA");
        Log::info($tm);
        // return ['' => $tm];
        return [
            'id'       => $tm->id,
            'type'     => $type,
            'title'    => $data->title ?? $data->name,
            'division' => $data->division->name ?? null,

            'dateStr'  => $tm->start_time,
            'startDate' => $tm->start_time,
            'endDate'   => $tm->end_time,

            'pic'      => $data->pic,
            'location' => $tm->location,
            "documents" => $tm->documents
        ] + ($type === 'proker'
            ? ['progress' => $data->progress]
            : []);
    }
}
