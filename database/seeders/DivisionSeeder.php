<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            [
                'name'             => 'KWSBK',
                'full_name'        => 'Khusus ketua,wakil ketua,sekertaris umum,bendahara umum, ketua divisi',
                'color'            => '#01091a',
                'color_light'      => '#DBEAFE',
                'color_soft'       => '#EFF6FF',
                'icon'             => 'bi-shield-check',
                'visibility'             => 'only_roles',
                'description'      => 'Pengawasan kepatuhan terhadap AD/ART dan evaluasi kinerja pengurus organisasi.',
                'vision'           => 'Menjadi badan pengawas yang independen, adil, dan transparan dalam menjaga kepatuhan organisasi terhadap AD/ART.',
                'mission'          => 'Melakukan evaluasi berkala, menegakkan disiplin, dan memediasi konflik internal secara profesional.',
                'established_year' => 2018,
            ],
            [
                'name'             => 'KWSB',
                'full_name'        => 'Ketertiban, Wewenang & Badan Pengawas',
                'color'            => '#2563EB',
                'color_light'      => '#DBEAFE',
                'color_soft'       => '#EFF6FF',
                'icon'             => 'bi-shield-check',
                'description'      => 'Pengawasan kepatuhan terhadap AD/ART dan evaluasi kinerja pengurus organisasi.',
                'vision'           => 'Menjadi badan pengawas yang independen, adil, dan transparan dalam menjaga kepatuhan organisasi terhadap AD/ART.',
                'mission'          => 'Melakukan evaluasi berkala, menegakkan disiplin, dan memediasi konflik internal secara profesional.',
                'established_year' => 2018,
            ],
            [
                'name'             => 'Internal',
                'full_name'        => 'Hubungan Internal & Kaderisasi',
                'color'            => '#22C55E',
                'color_light'      => '#DCFCE7',
                'color_soft'       => '#F0FDF4',
                'icon'             => 'bi-people-fill',
                'description'      => 'Mengelola hubungan antar anggota dan program kaderisasi untuk pengembangan SDM.',
                'vision'           => 'Membangun solidaritas dan kekeluargaan yang kuat antar seluruh anggota himpunan.',
                'mission'          => 'Menyelenggarakan kegiatan kaderisasi, gathering, dan pengembangan kapasitas anggota.',
                'established_year' => 2017,
            ],
            [
                'name'             => 'Eksternal',
                'full_name'        => 'Hubungan Eksternal & Kemitraan',
                'color'            => '#F97316',
                'color_light'      => '#FFEDD5',
                'color_soft'       => '#FFF7ED',
                'icon'             => 'bi-globe2',
                'description'      => 'Membangun jejaring dan kerja sama dengan pihak eksternal dan industri.',
                'vision'           => 'Menjadi jembatan strategis antara himpunan mahasiswa dengan eksternal kampus dan industri.',
                'mission'          => 'Membangun kemitraan, menjalin relasi, dan menghadirkan peluang magang bagi mahasiswa.',
                'established_year' => 2017,
            ],
            [
                'name'             => 'Minbak',
                'full_name'        => 'Minat & Bakat / Penatausahaan',
                'color'            => '#8B5CF6',
                'color_light'      => '#EDE9FE',
                'color_soft'       => '#F5F3FF',
                'icon'             => 'bi-journal-text',
                'description'      => 'Penatausahaan administrasi, surat-menyurat, dan pengembangan minat bakat anggota.',
                'vision'           => 'Menjadi pusat penatausahaan yang rapi, terstruktur, dan kreatif dalam pengembangan minat-bakat.',
                'mission'          => 'Mengelola administrasi organisasi dan menyelenggarakan kegiatan minat-bakat yang inspiratif.',
                'established_year' => 2018,
            ],
            [
                'name'             => 'Sosma',
                'full_name'        => 'Sosial Masyarakat',
                'color'            => '#EF4444',
                'color_light'      => '#FEE2E2',
                'color_soft'       => '#FEF2F2',
                'icon'             => 'bi-heart-pulse',
                'description'      => 'Pengembangan sosial masyarakat dan kegiatan pengabdian kepada masyarakat.',
                'vision'           => 'Menjadi agen perubahan sosial yang berkontribusi nyata bagi masyarakat sekitar.',
                'mission'          => 'Menyelenggarakan bakti sosial, donor darah, dan program pengabdian masyarakat berkelanjutan.',
                'established_year' => 2017,
            ],
            [
                'name'             => 'Infokom',
                'full_name'        => 'Informasi & Komunikasi',
                'color'            => '#06B6D4',
                'color_light'      => '#CFFAFE',
                'color_soft'       => '#ECFEFF',
                'icon'             => 'bi-broadcast-pin',
                'description'      => 'Informasi, publikasi, dan pengelolaan sistem teknologi komunikasi organisasi.',
                'vision'           => 'Menjadi pusat informasi yang cepat, akurat, dan kreatif untuk seluruh kegiatan himpunan.',
                'mission'          => 'Mengelola media sosial, website, desain publikasi, dan dokumentasi visual organisasi.',
                'established_year' => 2016,
            ],
            [
                'name'             => 'KWU',
                'full_name'        => 'Kewirausahaan',
                'color'            => '#EAB308',
                'color_light'      => '#FEF3C7',
                'color_soft'       => '#FEFCE8',
                'icon'             => 'bi-bag-check',
                'description'      => 'Kewirausahaan dan pengembangan unit usaha organisasi himpunan mahasiswa.',
                'vision'           => 'Menjadi inkubator kewirausahaan mahasiswa yang menghasilkan entrepreneur muda.',
                'mission'          => 'Mengembangkan unit usaha, menyelenggarakan pelatihan, dan mengelola bazaar kewirausahaan.',
                'established_year' => 2019,
            ],
        ];

        foreach ($divisions as $division) {
            Division::create($division);
        }

        $this->command->info('✅ DivisionSeeder: 7 divisi berhasil dibuat.');
    }
}
