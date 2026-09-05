<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\roles;
use App\Models\Division;
use App\Models\DivisionMember;
use App\Models\Event;
use App\Models\DivisionWorkProgram;
use App\Models\roles_permisions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // ROLE
        // ============================================================

        $roles = [];

        foreach (
            [
                'admin',
                'kahim',
                'wakahim',
                'sekum',
                'bendum',
                'kadiv',
                'sekdiv',
                'sekdivInternal',
                'anggota',
            ] as $roleName
        ) {

            $isAdmin = false;
            if ($roleName === 'admin') {
                $isAdmin = true;
            }

            $roles[$roleName] = roles::firstOrCreate([
                'name' => $roleName,
                'is_admin' => $isAdmin
            ]);
        }

        // ============================================================
        // DIVISION
        // ============================================================

        $divisions = Division::pluck('id', 'name');

        $requiredDivisions = [
            'KWSBK',
            'KWSB',
            'Internal',
            'Eksternal',
            'Minbak',
            'Sosma',
            'Infokom',
            'KWU',
        ];

        foreach ($requiredDivisions as $divisionName) {
            if (!isset($divisions[$divisionName])) {
                throw new \Exception(
                    "Division '{$divisionName}' tidak ditemukan di database."
                );
            }
        }

        // ============================================================
        // ACTION
        // ============================================================

        $fullActions = 'C,R,U,D';
        $readOnly = 'R';

        // ============================================================
        // HELPER PERMISSION
        // ============================================================
        //
        // jenis = division
        //   -> permission berlaku untuk division milik user
        //   -> permissionable_type = null
        //   -> permissionable_id   = null
        //
        // jenis = costum
        //   -> permission berlaku untuk division tertentu
        //   -> permissionable_type = Division::class
        //   -> permissionable_id   = ID division
        //
        // ============================================================

        $permission = function (
            string $roleName,
            string $management,
            string $jenis,
            string $actions,
            ?int $divisionId = null
        ) use ($roles) {

            roles_permisions::updateOrCreate(
                [
                    'role_id' => $roles[$roleName]->id,
                    'management' => $management,
                    'jenis' => $jenis,

                    'permissionable_type' =>
                    $jenis === 'costum'
                        ? Division::class
                        : null,

                    'permissionable_id' =>
                    $jenis === 'costum'
                        ? $divisionId
                        : null,
                ],
                [
                    'actions' => $actions,
                ]
            );
        };

        // ============================================================
        // ROLE PERMISSION
        // ============================================================


        // ============================================================
        // 1. ADMIN
        // ============================================================
        //
        // Admin CRUD semua division.
        //
        // Sama seperti sekum:
        // - division sendiri = CRUD
        // - division lain = CRUD menggunakan costum
        //
        // ============================================================

        $permission(
            'admin',
            'event',
            'division',
            $fullActions
        );

        $permission(
            'admin',
            'workprogram',
            'division',
            $fullActions
        );

        foreach ($requiredDivisions as $divisionName) {

            $permission(
                'admin',
                'event',
                'costum',
                $fullActions,
                $divisions[$divisionName]
            );

            $permission(
                'admin',
                'workprogram',
                'costum',
                $fullActions,
                $divisions[$divisionName]
            );
        }


        // ============================================================
        // 2. KAHIM
        // ============================================================
        //
        // Kahim:
        //
        // READONLY:
        // - KWSB
        // - KWSBK
        //
        // Tidak menggunakan division sendiri.
        // Jadi semuanya costum.
        //
        // ============================================================

        foreach (
            [
                'KWSB',
                'KWSBK',
            ] as $divisionName
        ) {

            $permission(
                'kahim',
                'event',
                'costum',
                $readOnly,
                $divisions[$divisionName]
            );

            $permission(
                'kahim',
                'workprogram',
                'costum',
                $readOnly,
                $divisions[$divisionName]
            );
        }


        // ============================================================
        // 3. WAKAHIM
        // ============================================================
        //
        // Wakahim belum diberikan permission khusus tambahan.
        //
        // Kalau nanti mau READ KWSB/KWSBK bisa ditambahkan seperti
        // Kahim.
        //
        // ============================================================


        // ============================================================
        // 4. SEKUM
        // ============================================================
        //
        // Sekum:
        // CRUD SEMUA DIVISION.
        //
        // Division sendiri:
        //     jenis = division
        //
        // Division lainnya:
        //     jenis = costum
        //
        // ============================================================

        $permission(
            'sekum',
            'event',
            'division',
            $fullActions
        );

        $permission(
            'sekum',
            'workprogram',
            'division',
            $fullActions
        );

        foreach ($requiredDivisions as $divisionName) {

            $permission(
                'sekum',
                'event',
                'costum',
                $fullActions,
                $divisions[$divisionName]
            );

            $permission(
                'sekum',
                'workprogram',
                'costum',
                $fullActions,
                $divisions[$divisionName]
            );
        }


        // ============================================================
        // 5. BENDUM
        // ============================================================
        //
        // Tidak ada permission khusus.
        //
        // ============================================================


        // ============================================================
        // 6. KADIV
        // ============================================================
        //
        // Kadiv:
        //
        // READONLY di division sendiri
        //
        // + READONLY KWSBK
        //
        // Division sendiri:
        //     jenis = division
        //
        // KWSBK:
        //     jenis = costum
        //
        // ============================================================

        $permission(
            'kadiv',
            'event',
            'division',
            $readOnly
        );

        $permission(
            'kadiv',
            'workprogram',
            'division',
            $readOnly
        );

        $permission(
            'kadiv',
            'event',
            'costum',
            $readOnly,
            $divisions['KWSBK']
        );

        $permission(
            'kadiv',
            'workprogram',
            'costum',
            $readOnly,
            $divisions['KWSBK']
        );


        // ============================================================
        // 7. SEKDIV
        // ============================================================
        //
        // Sekdiv:
        //
        // CRUD di division sendiri.
        //
        // Tidak perlu costum.
        //
        // ============================================================

        $permission(
            'sekdiv',
            'event',
            'division',
            $fullActions
        );

        $permission(
            'sekdiv',
            'workprogram',
            'division',
            $fullActions
        );


        // ============================================================
        // 8. SEKDIV INTERNAL
        // ============================================================
        //
        // SekdivInternal:
        //
        // CRUD:
        //   - division sendiri
        //
        // READONLY:
        //   - Internal
        //   - Eksternal
        //   - Minbak
        //   - Sosma
        //   - Infokom
        //   - KWU
        //
        // TIDAK ADA AKSES:
        //   - KWSB
        //   - KWSBK
        //
        // ============================================================

        // Division sendiri
        $permission(
            'sekdivInternal',
            'event',
            'division',
            $fullActions
        );

        $permission(
            'sekdivInternal',
            'workprogram',
            'division',
            $fullActions
        );

        // Division lain yang readonly
        $readonlyDivisions = [
            'Internal',
            'Eksternal',
            'Minbak',
            'Sosma',
            'Infokom',
            'KWU',
        ];

        foreach ($readonlyDivisions as $divisionName) {

            $permission(
                'sekdivInternal',
                'event',
                'costum',
                $readOnly,
                $divisions[$divisionName]
            );

            $permission(
                'sekdivInternal',
                'workprogram',
                'costum',
                $readOnly,
                $divisions[$divisionName]
            );
        }


        // ============================================================
        // 9. ANGGOTA
        // ============================================================
        //
        // Anggota tidak diberikan permission khusus.
        //
        // ============================================================


        // ============================================================
        // USER - PIMPINAN HIMPUNAN
        // ============================================================

        User::create([
            'nim' => '210001',
            'name' => 'Ahmad Fauzi Rahman',
            'email' => 'kahim@hmj.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'angkatan' => '2021',
            'jabatan' => $roles['kahim']->id,
            'division' => $divisions['KWSB'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);
        User::create([
            'nim' => '111111',
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'angkatan' => '2021',
            'jabatan' => $roles['admin']->id,
            'division' => $divisions['KWSB'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        User::create([
            'nim' => '210002',
            'name' => 'Siti Nurhaliza',
            'email' => 'wakahim@hmj.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567891',
            'angkatan' => '2021',
            'jabatan' => $roles['wakahim']->id,
            'division' => $divisions['KWSB'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        User::create([
            'nim' => '210003',
            'name' => 'Budi Santoso',
            'email' => 'sekum1@hmj.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567892',
            'angkatan' => '2022',
            'jabatan' => $roles['sekum']->id,
            'division' => $divisions['Minbak'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        User::create([
            'nim' => '210004',
            'name' => 'Ani Kusuma',
            'email' => 'sekum2@hmj.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567893',
            'angkatan' => '2022',
            'jabatan' => $roles['sekum']->id,
            'division' => $divisions['Minbak'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        User::create([
            'nim' => '210005',
            'name' => 'Rudi Hermawan',
            'email' => 'bendum1@hmj.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567894',
            'angkatan' => '2022',
            'jabatan' => $roles['bendum']->id,
            'division' => $divisions['Minbak'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        User::create([
            'nim' => '210006',
            'name' => 'Rina Permata',
            'email' => 'bendum2@hmj.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567895',
            'angkatan' => '2022',
            'jabatan' => $roles['bendum']->id,
            'division' => $divisions['Minbak'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);


        // ============================================================
        // USER - KEPALA DIVISI
        // ============================================================

        $kadivs = [
            [
                'nim' => '220001',
                'name' => 'Rizki Maulana',
                'email' => 'rizki.m@kampus.ac.id',
                'division' => 'KWSB',
            ],
            [
                'nim' => '220002',
                'name' => 'Dewi Anjani',
                'email' => 'dewi.a@kampus.ac.id',
                'division' => 'Internal',
            ],
            [
                'nim' => '220003',
                'name' => 'Hendra Wijaya',
                'email' => 'hendra.w@kampus.ac.id',
                'division' => 'Eksternal',
            ],
            [
                'nim' => '220004',
                'name' => 'Sari Wulandari',
                'email' => 'sari.w@kampus.ac.id',
                'division' => 'Minbak',
            ],
            [
                'nim' => '220005',
                'name' => 'Bayu Setiawan',
                'email' => 'bayu.s@kampus.ac.id',
                'division' => 'Sosma',
            ],
            [
                'nim' => '220006',
                'name' => 'Nadia Pramita',
                'email' => 'nadia.p@kampus.ac.id',
                'division' => 'Infokom',
            ],
            [
                'nim' => '220007',
                'name' => 'Fajar Nugroho',
                'email' => 'fajar.n@kampus.ac.id',
                'division' => 'KWU',
            ],
        ];

        $kadivUsers = [];

        foreach ($kadivs as $i => $data) {

            $divisionId = $divisions[$data['division']];

            $user = User::create([
                'nim' => $data['nim'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'phone' => '08123456' . str_pad(
                    900 + $i,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),
                'angkatan' => '2022',
                'jabatan' => $roles['kadiv']->id,
                'division' => $divisionId,
                'sub_divisi' => null,
                'status' => 'aktif',
            ]);

            $kadivUsers[$data['division']] = $user;

            DivisionMember::create([
                'division_id' => $divisionId,
                'user_id' => $user->id,
                'position' => 'Koordinator',
                'batch' => '2022',
                'email' => $user->email,
                'phone' => $user->phone,
            ]);
        }


        // ============================================================
        // USER - ANGGOTA INTERNAL
        // ============================================================

        $anggotaInternal = User::create([
            'nim' => '230001',
            'name' => 'Putri Rahayu',
            'email' => 'anggota@kampus.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567903',
            'angkatan' => '2023',
            'jabatan' => $roles['anggota']->id,
            'division' => $divisions['Internal'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        DivisionMember::create([
            'division_id' => $divisions['Internal'],
            'user_id' => $anggotaInternal->id,
            'position' => 'Anggota',
            'batch' => '2023',
            'email' => $anggotaInternal->email,
            'phone' => $anggotaInternal->phone,
        ]);


        // ============================================================
        // USER - SEKDIV
        // ============================================================

        $sekdivInternal = User::create([
            'nim' => '220008',
            'name' => 'Sekdiv Internal',
            'email' => 'sekdiv.internal@kampus.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234569008',
            'angkatan' => '2022',
            'jabatan' => $roles['sekdivInternal']->id,
            'division' => $divisions['Internal'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        DivisionMember::create([
            'division_id' => $divisions['Internal'],
            'user_id' => $sekdivInternal->id,
            'position' => 'Sekretaris Divisi',
            'batch' => '2022',
            'email' => $sekdivInternal->email,
            'phone' => $sekdivInternal->phone,
        ]);

        // Contoh Sekdiv Eksternal
        $sekdivEksternal = User::create([
            'nim' => '220009',
            'name' => 'Sekdiv Eksternal',
            'email' => 'sekdiv.eksternal@kampus.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234569009',
            'angkatan' => '2022',
            'jabatan' => $roles['sekdiv']->id,
            'division' => $divisions['Eksternal'],
            'sub_divisi' => null,
            'status' => 'aktif',
        ]);

        DivisionMember::create([
            'division_id' => $divisions['Eksternal'],
            'user_id' => $sekdivEksternal->id,
            'position' => 'Sekretaris Divisi',
            'batch' => '2022',
            'email' => $sekdivEksternal->email,
            'phone' => $sekdivEksternal->phone,
        ]);


        // ============================================================
        // WORK PROGRAM
        // ============================================================

        $proker1 = DivisionWorkProgram::create([
            'division_id' => $divisions['Internal'],
            'name' => 'Kaderisasi Anggota Baru',
            'allowed' => 'publik',
            'pic' => 'Dewi Anjani',
            'status' => 'Berlangsung',
            'progress' => 75,
        ]);

        $proker2 = DivisionWorkProgram::create([
            'division_id' => $divisions['Eksternal'],
            'name' => 'Kerja Sama Industri',
            'allowed' => 'publik',
            'pic' => 'Hendra Wijaya',
            'status' => 'Persiapan',
            'progress' => 25,
        ]);

        $proker3 = DivisionWorkProgram::create([
            'division_id' => $divisions['KWU'],
            'name' => 'Bazar Kewirausahaan',
            'allowed' => 'anggota',
            'pic' => 'Fajar Nugroho',
            'status' => 'Mendatang',
            'progress' => 0,
        ]);

        $proker4 = DivisionWorkProgram::create([
            'division_id' => $divisions['Sosma'],
            'name' => 'Bakti Sosial',
            'allowed' => 'publik',
            'pic' => 'Bayu Setiawan',
            'status' => 'Selesai',
            'progress' => 100,
        ]);


        // ============================================================
        // EVENT
        // ============================================================

        Event::create([
            'event_id' => 'EVT-001',
            'title' => 'Rapat Internal Himpunan',
            'division_id' => $divisions['Internal'],
            'pic' => 'Dewi Anjani',
            'type_event' => 'meeting',
            'allowed' => 'divisi',
            'status' => 'Mendatang',
            'description' =>
            'Rapat internal untuk membahas agenda dan program kerja divisi.',
            'created_by' => 'Dewi Anjani',
        ]);

        Event::create([
            'event_id' => 'EVT-002',
            'title' => 'Bakti Sosial Himpunan',
            'division_id' => $divisions['Sosma'],
            'pic' => 'Bayu Setiawan',
            'type_event' => 'event',
            'allowed' => 'publik',
            'status' => 'Persiapan',
            'description' =>
            'Kegiatan sosial masyarakat bersama anggota himpunan.',
            'created_by' => 'Bayu Setiawan',
        ]);

        Event::create([
            'event_id' => 'EVT-003',
            'title' => 'Bazar Kewirausahaan',
            'division_id' => $divisions['KWU'],
            'pic' => 'Fajar Nugroho',
            'type_event' => 'event',
            'allowed' => 'anggota',
            'status' => 'Mendatang',
            'description' =>
            'Kegiatan bazar untuk mengembangkan jiwa kewirausahaan mahasiswa.',
            'created_by' => 'Fajar Nugroho',
        ]);

        Event::create([
            'event_id' => 'EVT-004',
            'title' => 'Seminar Kerja Sama Industri',
            'division_id' => $divisions['Eksternal'],
            'pic' => 'Hendra Wijaya',
            'type_event' => 'event',
            'allowed' => 'publik',
            'status' => 'Selesai',
            'description' =>
            'Seminar bersama mitra industri dan alumni.',
            'created_by' => 'Hendra Wijaya',
        ]);


        // ============================================================
        // SELESAI
        // ============================================================

        $this->command->info(
            'Roles, permissions, users, division members, work programs, dan events berhasil dibuat.'
        );
    }
}
