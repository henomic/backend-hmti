<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // ─── Index (Pimpinan only) ────────────────────────────────────

    public function index(Request $request)
    {
        // if (!auth()->user()->canManageUsers()) {
        //     return response()->json(['message' => 'Tidak memiliki izin untuk melihat daftar pengguna'], 403);
        // }

        $query = User::query();

        if ($request->filled('jabatan')) {
            $query->where('jabatan', $request->jabatan);
        }
        if ($request->filled('divisi')) {
            $query->where('divisi', $request->divisi);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('nim', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('name')->get());
    }

    // ─── Show ─────────────────────────────────────────────────────

    public function show($id)
    {
        // User bisa lihat profil sendiri, admin bisa lihat semua
        if (auth()->id() != $id && !auth()->user()->canManageUsers()) {
            return response()->json(['message' => 'Tidak memiliki izin'], 403);
        }

        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // ─── Update ───────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $authUser = auth()->user();


        // Hanya pimpinan yang bisa update user lain
        if (!$authUser->isAdmin()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk mengubah data pengguna lain'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'nim'       => 'required|string|max:255',
            'email'      => 'required|string|email|unique:users,email,' . $id,
            'phone'      => 'nullable|max:20',
            'angkatan'   => 'nullable|max:10',
            'jabatan'    => 'required',
            'division'     => 'required',
            'sub_divisi' => 'nullable|string|max:100',
            'status'     => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }


        $data = $request->only(['name', 'nim', 'email', 'phone', 'angkatan', 'jabatan', 'division', 'sub_divisi', 'status']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui',
            'data'    => $user,
        ]);
    }

    // ─── Destroy (Pimpinan only) ──────────────────────────────────

    public function destroy(int $id)
    {

        Log::info("id nya ni");
        Log::info($id);


        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Tidak memiliki izin untuk menghapus pengguna'], 403);
            }


            $user = User::findOrFail($id);
            $user->delete();

            return response()->json(['message' => 'Pengguna berhasil dihapus']);
        } catch (\Throwable $th) {
            Log::info("error nya di delete user");
            Log::info($th->getMessage());
        }
    }


    public function store(Request $request)
    {
        $authUser = auth()->user();

        // Hanya pimpinan yang bisa membuat user baru
        if (!$authUser->isAdmin()) {
            return response()->json([
                'message' => 'Tidak memiliki izin untuk membuat pengguna baru'
            ], 403);
        }

        Log::info("request smua nya");
        Log::info($request->all());

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'nim'       => 'required|string|max:255',
            'email'      => 'required|string|email|unique:users,email',
            'phone'      => 'nullable|max:20',
            'angkatan'   => 'nullable|max:10',
            'jabatan'    => 'required',
            'division'     => 'required',
            'sub_divisi' => 'nullable|string|max:100',
            'status'     => 'required',
            'password'   => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name'       => $request->name,
            'nim'       => $request->nim,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'angkatan'   => $request->angkatan,
            'jabatan'    => $request->jabatan,
            'division'     => $request->division,
            'sub_divisi' => $request->sub_divisi,
            'status'     => $request->status,
            'password'   => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil dibuat',
            'data'    => $user,
        ], 201);
    }


    // ─── Dashboard Stats ──────────────────────────────────────────

    public function dashboardStats()
    {
        $divisions = ['KWSB', 'Internal', 'Eksternal', 'Minbak', 'Sosma', 'Infokom', 'KWU'];

        $divisionStats = [];
        foreach ($divisions as $div) {
            $divisionStats[$div] = Event::where('division', $div)->count();
        }

        $recentEvents = Event::with(['rundowns', 'documents'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'id'       => $e->id,
                'event_id' => $e->event_id,
                'title'    => $e->title,
                'division' => $e->division,
                'status'   => $e->status,
                'start'    => $e->start_time?->toISOString(),
            ]);

        return response()->json([
            'total_users'      => User::count(),
            'active_users'     => User::where('status', 'aktif')->count(),
            'total_events'     => Event::count(),
            'upcoming_events'  => Event::where('status', 'Mendatang')->count(),
            'ongoing_events'   => Event::where('status', 'Berlangsung')->count(),
            'completed_events' => Event::where('status', 'Selesai')->count(),
            'division_stats'   => $divisionStats,
            'recent_events'    => $recentEvents,
        ]);
    }
}
