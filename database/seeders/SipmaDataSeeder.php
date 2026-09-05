<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SipmaDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi Divisi
        $divisions = [
            'KWSB'      => ['nama' => 'Ketertiban, Wewenang & Badan Pengawas', 'warna' => '#2563EB', 'icon' => 'bi-shield-check'],
            'Internal'  => ['nama' => 'Hubungan Internal & Kaderisasi', 'warna' => '#22C55E', 'icon' => 'bi-people-fill'],
            'Eksternal' => ['nama' => 'Hubungan Eksternal & Kemitraan', 'warna' => '#F97316', 'icon' => 'bi-globe2'],
            'Minbak'    => ['nama' => 'Minat & Bakat / Penatausahaan', 'warna' => '#8B5CF6', 'icon' => 'bi-journal-text'],
            'Sosma'     => ['nama' => 'Sosial Masyarakat', 'warna' => '#EF4444', 'icon' => 'bi-heart-pulse'],
            'Infokom'   => ['nama' => 'Informasi & Komunikasi', 'warna' => '#06B6D4', 'icon' => 'bi-broadcast-pin'],
            'KWU'       => ['nama' => 'Kewirausahaan', 'warna' => '#EAB308', 'icon' => 'bi-bag-check'],
        ];

        $divMap = [];
        foreach ($divisions as $kode => $meta) {
            $divMap[$kode] = Division::create([
                'kode' => $kode,
                'nama' => $meta['nama'],
                'warna' => $meta['warna'],
                'icon' => $meta['icon'],
            ]);
        }

        // 2. Akun Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@sipma.hmj.ac.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // 3. Akun Kahim & Wakahim
        User::create([
            'name' => 'Ahmad Fauzi Rahman',
            'email' => 'kahim@kampus.ac.id',
            'password' => Hash::make('password123'),
            'jabatan' => 'kahim',
            'role' => 'pimpinan',
        ]);

        User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'wakahim@kampus.ac.id',
            'password' => Hash::make('password123'),
            'jabatan' => 'wakahim',
            'role' => 'pimpinan',
        ]);

        // 4. Kadiv Internal & Anggota Minbak Contoh
        $kadivInternal = User::create([
            'name' => 'Dewi Anjani',
            'email' => 'dewi.a@kampus.ac.id',
            'password' => Hash::make('password123'),
            'division_id' => $divMap['Internal']->id,
            'jabatan' => 'kadiv',
            'role' => 'kadiv',
        ]);

        User::create([
            'name' => 'Sari Wulandari',
            'email' => 'sari.w@kampus.ac.id',
            'password' => Hash::make('password123'),
            'division_id' => $divMap['Minbak']->id,
            'jabatan' => 'anggota',
            'sub_bagian' => 'akademik',
            'role' => 'anggota',
        ]);

        // 5. Kegiatan Dummy Pertama
        $event = Event::create([
            'division_id' => $divMap['Internal']->id,
            'pic_id' => $kadivInternal->id,
            'title' => 'Rapat Pleno Evaluasi Semester Genap',
            'type_event' => "meeting",
            'location' => 'Ruang Rapat Utama, Gedung A',
            'description' => 'Evaluasi menyeluruh terhadap pelaksanaan program kerja.',
            'status' => 'Berlangsung',
        ]);

        $event->timetables()->createMany([
            [
                'type' => 'pembukaan',
                'location' => 'Ruang Rapat Utama, Gedung A',
                'start_time' => '2026-08-22 14:00:00',
                'end_time' => '2026-08-22 14:15:00',
            ],
            [
                'type' => 'laporan',
                'location' => 'Ruang Rapat Utama, Gedung A',
                'start_time' => '2026-08-22 14:15:00',
                'end_time' => '2026-08-22 15:50:00',
            ],
            [
                'type' => 'penutup',
                'location' => 'Ruang Rapat Utama, Gedung A',
                'start_time' => '2026-08-22 15:50:00',
                'end_time' => '2026-08-22 17:00:00',
            ],
        ]);
    }
}
