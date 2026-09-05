<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRundown;
use App\Models\EventDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    // ─── Index (Public) ───────────────────────────────────────────

    public function index(Request $request)
    {


        try {
            $query = Event::with(['rundowns', 'documents', 'timetables']);

            // Filter by division
            if ($request->filled('division') && $request->division !== 'all') {
                $query->where('division', $request->division);
            }

            // Filter by status
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by month
            if ($request->filled('month') && $request->month !== 'all') {
                // $query->whereMonth('start_time', $request->month);
            }

            // Filter by year
            if ($request->filled('year') && $request->year !== 'all') {
                // $query->whereYear('start_time', $request->year);
            }

            // Search by title, division, pic
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('division', 'LIKE', "%{$search}%")
                        ->orWhere('pic', 'LIKE', "%{$search}%");
                });
            }

            $events = $query->get();
            // ->orderBy('start_time', 'asc')
            Log::info($events->map(fn($e) => $this->formatEvent($e)));

            return response()->json($events->map(fn($e) => $this->formatEvent($e)));
        } catch (\Throwable $e) {

            Log::error('=== Event controller error ===', [
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

    // ─── Check Date Conflict (Public / Admin) ─────────────────────

    public function checkConflict(Request $request)
    {
        $date = $request->input('date') ?? $request->input('start_time');
        if (!$date) {
            return response()->json(['message' => 'Tanggal harus diisi'], 422);
        }

        $targetDate = substr($date, 0, 10);
        $excludeEventId = $request->input('event_id');
        $excludeProkerId = $request->input('proker_id');

        $eventQuery = Event::whereDate('start_time', $targetDate);
        if ($excludeEventId) {
            $eventQuery->where('id', '!=', $excludeEventId);
        }
        $conflictingEvents = $eventQuery->get()->map(fn($e) => [
            'type'     => 'agenda',
            'id'       => $e->id,
            'title'    => $e->title,
            'division' => $e->division->division->name,
            'status'   => $e->status,
        ]);

        $prokerQuery = \App\Models\DivisionWorkProgram::where('date', 'LIKE', "%{$targetDate}%")
            ->orWhere('date', 'LIKE', '%' . date('Y-m', strtotime($targetDate)) . '%'); // some prokers just have "Maret 2026"
        // To be safe, exact match if it's a full date string, or partial for month-year
        $prokerQuery = \App\Models\DivisionWorkProgram::where(function ($q) use ($targetDate) {
            $q->where('date', $targetDate)
                ->orWhere('date', 'LIKE', "%" . date('Y-m', strtotime($targetDate)) . "%")
                ->orWhere('date', 'LIKE', "%" . strftime('%B %Y', strtotime($targetDate)) . "%"); // e.g. "Agustus 2026"
        });

        if ($excludeProkerId) {
            $prokerQuery->where('id', '!=', $excludeProkerId);
        }
        $conflictingProkers = $prokerQuery->get()->map(fn($p) => [
            'type'     => 'proker',
            'id'       => $p->id,
            'title'    => $p->name,
            'division' => $p->division->name ?? 'Unknown',
            'status'   => $p->status,
        ]);

        $conflicts = $conflictingEvents->merge($conflictingProkers);
        $hasConflict = $conflicts->count() > 0;

        return response()->json([
            'date'               => $targetDate,
            'has_conflict'       => $hasConflict,
            'conflicting_events' => $conflicts,
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────

    public function store(Request $request)
    {

        try {
            // Check permission
            // if (!auth()->user()->isAdmin()) {
            //     return response()->json(['message' => 'Tidak memiliki izin untuk membuat kegiatan'], 403);
            // }

            $validator = Validator::make($request->all(), [
                'title'              => 'required|string|max:255',
                'division'           => 'required',
                'pic'                => 'required|string|max:255',
                'type_event'         => 'required',
                'status'             => 'required|string',
                'description'        => 'nullable|string',
                'allowed'        => 'required|string',


            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            // Kadiv hanya bisa membuat event untuk divisinya sendiri
            if (auth()->user()->isKadiv() && $request->division !== auth()->user()->divisi) {
                return response()->json(['message' => 'Kadiv hanya dapat membuat kegiatan untuk divisinya sendiri'], 403);
            }

            $event = Event::create([
                'title'       => $request->title,
                'division_id'    => $request->division,
                'pic'         => $request->pic,
                "type_event" => $request->type_event,
                'status'      => $request->status,
                'description' => $request->description,
                'allowed' => $request->allowed,
                'created_by'  => auth()->user()->name,
            ]);

            // Buat rundown
            if (!empty($request->rundown)) {
                foreach ($request->rundown as $index => $item) {
                    EventRundown::create([
                        'event_id'    => $event->id,
                        'time'        => $item['time'],
                        'description' => $item['desc'],
                        'order'       => $index + 1,
                    ]);
                }
            }

            // Buat dokumen
            // if (!empty($request->docs)) {
            //     foreach ($request->docs as $icon) {
            //         EventDocument::create([
            //             'event_id' => $event->id,
            //             'icon'     => $icon,
            //         ]);
            //     }
            // }

            $event->load(['rundowns', 'documents']);

            return response()->json([
                'message' => 'Kegiatan berhasil dibuat',
                'data'    => $this->formatEvent($event),
            ], 201);
        } catch (\Throwable $th) {
            Log::info("Error di insert data event");
            Log::info($th->getMessage());
        }
    }

    // ─── Show ─────────────────────────────────────────────────────

    public function show($id)
    {
        $event = Event::with(['rundowns', 'documents'])->findOrFail($id);
        return response()->json($this->formatEvent($event));
    }

    // ─── Update ───────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        // if (!auth()->user()->canEditEvent()) {
        //     return response()->json(['message' => 'Tidak memiliki izin untuk mengubah kegiatan'], 403);
        // }

        Log::info("ini cek request memek");
        Log::info($request->all());

        // Kadiv hanya bisa edit event divisinya sendiri
        if (auth()->user()->isKadiv() && $event->division->name !== auth()->user()->divisi) {
            return response()->json(['message' => 'Kadiv hanya dapat mengubah kegiatan divisinya sendiri'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'      => 'sometimes|string|max:255',
            'pic'        => 'sometimes|string|max:255',
            'status'     => 'sometimes',
            'allowed'     => 'sometimes',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }
        // error update only pasti nya
        $event->update([
            'title' => $request->title,
            'division_id' => $request->division,
            'pic' => $request->pic,
            'type_event' => $request->type_event,
            'status' => $request->status,
            'description' => $request->description,
            'allowed' => $request->allowed,
        ]);

        $event->load(['rundowns', 'documents']);

        return response()->json([
            'message' => 'Kegiatan berhasil diperbarui',
            'data'    => $this->formatEvent($event),
        ]);
    }

    // ─── Destroy ──────────────────────────────────────────────────

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        // if (!auth()->user()->canDeleteEvent()) {
        //     return response()->json(['message' => 'Tidak memiliki izin untuk menghapus kegiatan'], 403);
        // }

        $event->delete();

        return response()->json(['message' => 'Kegiatan berhasil dihapus']);
    }

    // ─── Update Status ────────────────────────────────────────────

    public function updateStatus(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        // if (!auth()->user()->canEditEvent()) {
        //     return response()->json(['message' => 'Tidak memiliki izin untuk mengubah status kegiatan'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Mendatang,Berlangsung,Selesai,Dibatalkan,Persiapan',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $event->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status kegiatan berhasil diperbarui',
            'data'    => $this->formatEvent($event->load(['rundowns', 'documents'])),
        ]);
    }

    // ─── Format Response Helper ───────────────────────────────────

    private function formatEvent(Event $event): array
    {
        $startStr = $event->start_time ? $event->start_time->format('Y-m-d') : null;
        $endStr = $event->end_time ? $event->end_time->format('Y-m-d') : $startStr;
        // data nya aja
        return [
            'id'          => $event->id,
            'event_id'    => $event->event_id,
            'title'       => $event->title,
            'name'        => $event->title,
            'division'    => $event->division->name,
            'pic'         => $event->pic,
            'start'       => $event->start_time?->toISOString(),
            'end'         => $event->end_time?->toISOString(),
            'start_time'  => $startStr,
            'end_time'    => $endStr,
            'date'        => $startStr,
            'location'    => $event->location ?? null,
            'status'      => $event->status,
            'description' => $event->description,
            'created_by'  => $event->created_by,
            'rundown'     => $event->rundowns->map(fn($r) => [
                'time' => $r->time,
                'desc' => $r->description,
            ])->values(),
            "timetables" => $event->timetables,
            'docs' => $event->documents->pluck('icon')->values(),
        ];
    }
}
