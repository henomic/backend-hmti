<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nim',
        'name',
        'email',
        'password',
        'phone',
        'angkatan',
        'jabatan',
        'division',
        'sub_divisi',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];




    // ─── Relations ───────────────────────────────────────────────

    /**
     * Get the division associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */

    public function role(): BelongsTo
    {
        return $this->belongsTo(roles::class, 'jabatan', 'id');
    }


    // ─── Role Helpers ─────────────────────────────────────────────



    public function permissions()
    {
        return $this->role?->permision
            ->map(function ($permission) {

                $permissionableId = $permission->permissionable_id;
                Log::info("ini id nya");
                Log::info($permissionableId);
                if ($permission->jenis === 'division') {
                    $permissionableId = $this->division;
                }

                return [
                    'id' => $permission->id,
                    'management' => $permission->management,
                    'jenis' => $permission->jenis,
                    'role_id' => $permission->role_id,
                    'permissionable_type' => $permission->jenis == "division" ? Division::class : $permission->permissionable_type,
                    'permissionable_id' => $permission->jenis == "division" ? $this->division : $permissionableId,
                    'actions' => array_map(
                        'strtoupper',
                        array_map(
                            'trim',
                            explode(',', $permission->actions)
                        )
                    ),
                ];
            })
            ->values()
            ->toArray() ?? [];
    }


    public function isAdmin(): bool
    {
        return $this->role->is_admin;
    }

    public function canManageData(
        array $data,
        array $managements,
        array $actions,
        $resourceId = null
    ): bool {

        /*
    |--------------------------------------------------------------------------
    | Normalisasi management
    |--------------------------------------------------------------------------
    */
        $managements = collect($managements)
            ->map(fn($value) => strtolower(trim($value)))
            ->values()
            ->all();

        /*
    |--------------------------------------------------------------------------
    | Mapping action
    |--------------------------------------------------------------------------
    */
        $allowedActions = [
            'CREATE' => 'C',
            'READ'   => 'R',
            'UPDATE' => 'U',
            'DELETE' => 'D',

            'C' => 'C',
            'R' => 'R',
            'U' => 'U',
            'D' => 'D',
        ];

        $actions = collect($actions)
            ->map(fn($value) => strtoupper(trim($value)))
            ->map(fn($value) => $allowedActions[$value] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        /*
    |--------------------------------------------------------------------------
    | Tidak ada data / management / action valid
    |--------------------------------------------------------------------------
    */
        if (
            empty($data) ||
            empty($managements) ||
            empty($actions)
        ) {
            return false;
        }

        /*
    |--------------------------------------------------------------------------
    | Cek setiap permission
    |--------------------------------------------------------------------------
    */
        foreach ($data as $permission) {

            /*
        |--------------------------------------------------------------------------
        | Pastikan format permission valid
        |--------------------------------------------------------------------------
        */
            if (!is_array($permission)) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | 1. Management
        |--------------------------------------------------------------------------
        */
            $management = strtolower(
                trim($permission['management'] ?? '')
            );

            if (!in_array($management, $managements, true)) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | 2. Action
        |--------------------------------------------------------------------------
        */
            $permissionActions = $permission['actions'] ?? [];

            if (!is_array($permissionActions)) {
                continue;
            }

            $permissionActions = collect($permissionActions)
                ->map(fn($value) => strtoupper(trim($value)))
                ->map(fn($value) => $allowedActions[$value] ?? null)
                ->filter()
                ->values()
                ->all();

            /*
        |--------------------------------------------------------------------------
        | Minimal satu action harus cocok
        |--------------------------------------------------------------------------
        */
            if (
                empty(array_intersect(
                    $actions,
                    $permissionActions
                ))
            ) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | 3. Resource ID
        |
        | Kalau null:
        | ID tidak perlu dicek.
        |
        | Kalau ada:
        | permissionable_id harus sama.
        |--------------------------------------------------------------------------
        */
            if ($resourceId !== null) {

                if (
                    !isset($permission['permissionable_id'])
                ) {
                    continue;
                }

                if (
                    (int) $permission['permissionable_id']
                    !== (int) $resourceId
                ) {
                    continue;
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Semua syarat terpenuhi
        |--------------------------------------------------------------------------
        */
            return true;
        }

        /*
    |--------------------------------------------------------------------------
    | Tidak ada permission yang cocok
    |--------------------------------------------------------------------------
    */
        return false;
    }



    public function canManage(
        string $management,
        string $action,
        $resourceId = null
    ): bool {
        $permissions = $this->permissions();

        $action = strtoupper(trim($action));

        // Mapping action
        $allowedActions = [
            'CREATE' => 'C',
            'READ'   => 'R',
            'UPDATE' => 'U',
            'DELETE' => 'D',

            'C' => 'C',
            'R' => 'R',
            'U' => 'U',
            'D' => 'D',
        ];

        $action = $allowedActions[$action] ?? null;

        // Action tidak valid
        if ($action === null) {
            return false;
        }

        foreach ($permissions as $permission) {

            // =====================================================
            // 1. Management harus cocok
            // =====================================================
            if ($permission['management'] !== $management) {
                continue;
            }

            // =====================================================
            // 2. Action harus tersedia
            // =====================================================
            if (!in_array($action, $permission['actions'], true)) {
                continue;
            }

            // =====================================================
            // 3. Tidak ada resource ID
            // =====================================================
            //
            // Contoh:
            // canManage('event', 'C')
            //
            // Berarti cukup punya permission event + C.
            //
            if ($resourceId === null) {
                return true;
            }

            // =====================================================
            // 4. Permission berdasarkan DIVISION
            // =====================================================
            //
            // Contoh:
            // management = workprogram
            // jenis      = division
            //
            // Maka resourceId harus merupakan resource
            // yang berada di division user.
            //
            if ($permission['jenis'] === 'division') {

                // Untuk sementara kita hanya punya ID.
                // Jadi di sini permissionable_id yang sudah
                // di-resolve dari permissions() dibandingkan
                // dengan resourceId.
                if (
                    isset($permission['permissionable_id']) &&
                    (string) $permission['permissionable_id'] === (string) $resourceId
                ) {
                    return true;
                }

                continue;
            }

            // =====================================================
            // 5. Permission berdasarkan resource tertentu
            // =====================================================
            if ($permission['jenis'] === 'costum') {

                if (
                    isset($permission['permissionable_id']) &&
                    (string) $permission['permissionable_id'] === (string) $resourceId
                ) {
                    return true;
                }
            }
        }

        return false;
    }


    public function CanManageDivisionsData(
        array $management,
        array $action,
        array $resourceId = []
    ): array {
        $permissions = $this->permissions();
        Log::info("permisiopn data nya");
        Log::info($permissions);
        $data = [];

        /*
    |--------------------------------------------------------------------------
    | Normalisasi action
    |--------------------------------------------------------------------------
    */
        $allowedActions = [
            'CREATE' => 'C',
            'READ'   => 'R',
            'UPDATE' => 'U',
            'DELETE' => 'D',

            'C' => 'C',
            'R' => 'R',
            'U' => 'U',
            'D' => 'D',
        ];

        $actions = collect($action)
            ->map(fn($value) => strtoupper(trim($value)))
            ->map(fn($value) => $allowedActions[$value] ?? null)
            ->filter()
            ->values()
            ->all();

        /*
    |--------------------------------------------------------------------------
    | Action tidak valid
    |--------------------------------------------------------------------------
    */
        if (empty($actions)) {
            return [];
        }

        /*
    |--------------------------------------------------------------------------
    | Loop semua permission user
    |--------------------------------------------------------------------------
    */
        foreach ($permissions as $permission) {

            /*
        |--------------------------------------------------------------------------
        | 1. Management harus cocok
        |--------------------------------------------------------------------------
        */
            if (!in_array($permission['management'], $management, true)) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | 2. Action harus cocok
        |--------------------------------------------------------------------------
        */
            $permissionActions = $permission['actions'] ?? [];

            if (
                empty(array_intersect(
                    $actions,
                    $permissionActions
                ))
            ) {
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | 3. Kalau resourceId kosong
        |
        | Ambil semua permission yang cocok.
        |--------------------------------------------------------------------------
        */
            if (empty($resourceId)) {
                $data[] = $permission;
                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | 4. Kalau resourceId diberikan,
        |    permissionable_id harus cocok.
        |--------------------------------------------------------------------------
        */
            if (
                isset($permission['permissionable_id']) &&
                in_array(
                    (int) $permission['permissionable_id'],
                    array_map('intval', $resourceId),
                    true
                )
            ) {
                $data[] = $permission;
            }
        }

        return $data;
    }


    public function canManageById(
        string $managementPembanding,
        string $action,
        array $data = [],
        $resourceId = null
    ): bool {


        $action = strtoupper(trim($action));

        // Mapping action
        $allowedActions = [
            'CREATE' => 'C',
            'READ'   => 'R',
            'UPDATE' => 'U',
            'DELETE' => 'D',

            'C' => 'C',
            'R' => 'R',
            'U' => 'U',
            'D' => 'D',
        ];

        $action = $allowedActions[$action] ?? null;

        // Action tidak valid
        if ($action === null) {
            return false;
        }

        if (!in_array($data)) {
            return false;
        }
        if ($managementPembanding !== $data['management']) {
            return false;
        }
        if (!in_array($action, $data['actions'])) {
            return false;
        }
        if ($resourceId !== null) {
            if ($resourceId !== $data['permissionable_id ']) {
                return false;
            }
        }


        return true;
    }


    public function getManageableDivisions(
        array $managements,
        string $action
    ): array {

        $permissions = $this->permissions();

        // =========================================================
        // NORMALISASI ACTION
        // =========================================================

        $action = strtoupper(trim($action));

        $allowedActions = [
            'CREATE' => 'C',
            'READ'   => 'R',
            'UPDATE' => 'U',
            'DELETE' => 'D',
            'C'      => 'C',
            'R'      => 'R',
            'U'      => 'U',
            'D'      => 'D',
        ];

        $action = $allowedActions[$action] ?? null;

        if ($action === null) {
            return [];
        }

        // =========================================================
        // HASIL AKHIR DIVISION ID
        // =========================================================

        $divisionIds = [];

        // =========================================================
        // RESOURCE CUSTOM / MORPH
        // =========================================================

        $customResources = [];

        // =========================================================
        // RESOURCE JENIS DIVISION
        //
        // Untuk permission:
        //
        // division     -> permissionable_id = division_id
        // event        -> permissionable_id = event_id
        // workprogram  -> permissionable_id = workprogram_id
        // =========================================================

        $divisionResources = [
            'event'       => [],
            'workprogram' => [],
        ];

        // =========================================================
        // PROSES SEMUA PERMISSION
        // =========================================================

        foreach ($permissions as $permission) {

            // -----------------------------------------------------
            // MANAGEMENT
            // -----------------------------------------------------

            if (!in_array(
                $permission['management'],
                $managements,
                true
            )) {
                continue;
            }

            // -----------------------------------------------------
            // ACTION
            // -----------------------------------------------------

            if (!in_array(
                $action,
                $permission['actions'],
                true
            )) {
                continue;
            }

            $jenis      = $permission['jenis'];
            $management = $permission['management'];
            $type       = $permission['permissionable_type'];
            $id         = $permission['permissionable_id'];

            if ($id === null) {
                continue;
            }

            // =====================================================
            // 1. JENIS = DIVISION
            // =====================================================

            if ($jenis === 'division') {

                // -------------------------------------------------
                // MANAGEMENT DIVISION
                //
                // permissionable_id sudah merupakan division_id
                // -------------------------------------------------

                if ($management === 'division') {

                    $divisionIds[] = (int) $id;

                    continue;
                }

                // -------------------------------------------------
                // MANAGEMENT EVENT
                //
                // permissionable_id = event_id
                // -------------------------------------------------

                if ($management === 'event') {

                    $divisionResources['event'][] = (int) $id;

                    continue;
                }

                // -------------------------------------------------
                // MANAGEMENT WORKPROGRAM
                //
                // permissionable_id = workprogram_id
                // -------------------------------------------------

                if ($management === 'workprogram') {

                    $divisionResources['workprogram'][] = (int) $id;

                    continue;
                }

                continue;
            }

            // =====================================================
            // 2. JENIS = CUSTOM / MORPH
            // =====================================================

            if ($jenis === 'costum') {

                // Tidak punya morph type
                if ($type === null) {
                    continue;
                }

                // -------------------------------------------------
                // MORPH KE DIVISION
                //
                // PENTING:
                //
                // Jangan query Division::with('division')
                //
                // Karena Division sendiri sudah merupakan
                // division resource.
                // -------------------------------------------------

                if ($type === Division::class) {

                    $divisionIds[] = (int) $id;

                    continue;
                }

                // -------------------------------------------------
                // MORPH KE MODEL LAIN
                //
                // Contoh:
                //
                // DivisionWorkProgram
                // Event
                // Timetable
                // dll.
                //
                // Model tersebut harus mempunyai:
                //
                // public function division()
                //
                // -------------------------------------------------

                $customResources[$type][] = (int) $id;

                continue;
            }
        }

        // =========================================================
        // BATCH EVENT
        // =========================================================

        if (!empty($divisionResources['event'])) {

            $eventIds = array_values(
                array_unique(
                    $divisionResources['event']
                )
            );

            $events = Event::query()
                ->whereIn('id', $eventIds)
                ->get([
                    'id',
                    'division_id',
                ]);

            foreach ($events as $event) {

                if ($event->division_id !== null) {

                    $divisionIds[] = (int) $event->division_id;
                }
            }
        }

        // =========================================================
        // BATCH WORKPROGRAM
        // =========================================================

        if (!empty($divisionResources['workprogram'])) {

            $workProgramIds = array_values(
                array_unique(
                    $divisionResources['workprogram']
                )
            );

            $workPrograms = DivisionWorkProgram::query()
                ->whereIn('id', $workProgramIds)
                ->get([
                    'id',
                    'division_id',
                ]);

            foreach ($workPrograms as $workProgram) {

                if ($workProgram->division_id !== null) {

                    $divisionIds[] = (int) $workProgram->division_id;
                }
            }
        }

        // =========================================================
        // BATCH CUSTOM / MORPH
        // =========================================================

        foreach ($customResources as $modelClass => $ids) {

            $ids = array_values(
                array_unique($ids)
            );

            /*
        |--------------------------------------------------------------------------
        | Jangan pernah proses Division di sini
        |--------------------------------------------------------------------------
        |
        | Division sudah ditangani langsung di atas.
        |
        */

            if ($modelClass === Division::class) {
                foreach ($ids as $id) {
                    $divisionIds[] = (int) $id;
                }

                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | Model lain
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | App\Models\DivisionWorkProgram
        | App\Models\Event
        | App\Models\Timetable
        |
        | Model tersebut wajib punya:
        |
        | public function division()
        |
        */

            $resources = $modelClass::query()
                ->with('division')
                ->whereIn('id', $ids)
                ->get();

            foreach ($resources as $resource) {

                if ($resource->division) {

                    $divisionIds[] = (int) $resource->division->id;
                }
            }
        }

        // =========================================================
        // UNIQUE
        // =========================================================

        return array_values(
            array_unique($divisionIds)
        );
    }



    // public function getManageableDivisions(
    //     array $managements,
    //     string $action
    // ): array {

    //     $permissions = $this->permissions();

    //     $action = strtoupper(trim($action));

    //     $allowedActions = [
    //         'CREATE' => 'C',
    //         'READ'   => 'R',
    //         'UPDATE' => 'U',
    //         'DELETE' => 'D',
    //         'C'      => 'C',
    //         'R'      => 'R',
    //         'U'      => 'U',
    //         'D'      => 'D',
    //     ];

    //     $action = $allowedActions[$action] ?? null;

    //     if ($action === null) {
    //         return [];
    //     }

    //     $divisionIds = [];

    //     /*
    // |--------------------------------------------------------------------------
    // | MORPH RESOURCES
    // |--------------------------------------------------------------------------
    // */

    //     $morphResources = [];

    //     foreach ($permissions as $permission) {

    //         // Management tidak sesuai
    //         if (!in_array(
    //             $permission['management'],
    //             $managements,
    //             true
    //         )) {
    //             continue;
    //         }

    //         // Action tidak sesuai
    //         if (!in_array(
    //             $action,
    //             $permission['actions'],
    //             true
    //         )) {
    //             continue;
    //         }

    //         $type = $permission['permissionable_type'];
    //         $id   = $permission['permissionable_id'];

    //         if ($id === null) {
    //             continue;
    //         }

    //         /*
    //     |--------------------------------------------------------------------------
    //     | LANGSUNG DIVISION
    //     |--------------------------------------------------------------------------
    //     |
    //     | Tidak perlu query.
    //     | permissionable_id sudah merupakan division_id.
    //     |
    //     */

    //         if ($type === Division::class) {

    //             $divisionIds[] = (int) $id;

    //             continue;
    //         }

    //         /*
    //     |--------------------------------------------------------------------------
    //     | MODEL LAIN
    //     |--------------------------------------------------------------------------
    //     |
    //     | Nanti diambil batch dan menggunakan relasi division().
    //     |
    //     */

    //         if ($type !== null) {

    //             $morphResources[$type][] = (int) $id;
    //         }
    //     }

    //     /*
    // |--------------------------------------------------------------------------
    // | LOAD SEMUA MORPH SECARA BATCH
    // |--------------------------------------------------------------------------
    // */

    //     foreach ($morphResources as $modelClass => $ids) {

    //         $ids = array_values(
    //             array_unique($ids)
    //         );

    //         $resources = $modelClass::query()
    //             ->with('division')
    //             ->whereIn('id', $ids)
    //             ->get();

    //         foreach ($resources as $resource) {

    //             if ($resource->division) {

    //                 $divisionIds[] = (int) $resource->division->id;
    //             }
    //         }
    //     }

    //     /*
    // |--------------------------------------------------------------------------
    // | UNIQUE DIVISION ID
    // |--------------------------------------------------------------------------
    // */

    //     return array_values(
    //         array_unique($divisionIds)
    //     );
    // }



    public function isPimpinan(): bool
    {
        return in_array($this->jabatan, ['kahim', 'wakahim']);
    }

    public function isKadiv(): bool
    {
        return $this->jabatan === 'kadiv';
    }

    public function isAnggota(): bool
    {
        return $this->jabatan === 'anggota';
    }

    // ─── Permission Helpers ───────────────────────────────────────

    public function canManageEvent(): bool
    {
        return in_array($this->jabatan, ['kahim', 'wakahim', 'kadiv']);
    }




    public function canCreateEvent(): bool
    {
        return in_array($this->jabatan, ['kahim', 'wakahim', 'kadiv']);
    }

    public function canEditEvent(): bool
    {
        return in_array($this->jabatan, ['kahim', 'wakahim', 'kadiv']);
    }

    public function canDeleteEvent(): bool
    {
        return in_array($this->jabatan, ['kahim', 'wakahim']);
    }

    public function canManageUsers(): bool
    {
        return in_array($this->jabatan, ['kahim', 'wakahim']);
    }

    public function canManageDivisions(): bool
    {
        return in_array($this->jabatan, ['kahim', 'wakahim']);
    }
}
