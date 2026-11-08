<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Services\QrCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ResetAndSeedMembersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Matikan foreign key check untuk reset aman
        Schema::disableForeignKeyConstraints();

        // Bersihkan tabel data lama
        DB::table('attendances')->truncate();
        DB::table('meetings')->truncate();
        if (Schema::hasTable('event_members')) {
            DB::table('event_members')->truncate();
        }
        if (Schema::hasTable('feedbacks')) {
            DB::table('feedbacks')->truncate();
        }
        if (Schema::hasTable('announcement_reads')) {
            DB::table('announcement_reads')->truncate();
        }
        if (Schema::hasTable('announcements')) {
            DB::table('announcements')->truncate();
        }
        DB::table('users')->truncate();

        // Pastikan tabel admin memiliki minimal 1 akun admin
        if (Schema::hasTable('admin')) {
            $adminCount = DB::table('admin')->count();
            if ($adminCount === 0) {
                DB::table('admin')->insert([
                    'email' => 'admin@himati.id',
                    'password' => Hash::make('admin123'),
                    'nama' => 'Admin HIMA TI',
                    'created_at' => now(),
                ]);
            }
        }

        Schema::enableForeignKeyConstraints();

        // 2. Bersihkan file QR lama di disk
        $qrDir = public_path('assets/img/qrcodes');
        if (File::isDirectory($qrDir)) {
            $files = File::files($qrDir);
            foreach ($files as $file) {
                File::delete($file);
            }
        } else {
            File::makeDirectory($qrDir, 0755, true);
        }

        // 3. Data 41 Pengurus Resmi HIMA TI
        $defaultPassword = Hash::make('himati123');

        $membersData = [
            // --- Pengurus Inti ---
            [
                'nrp' => 'HMTI/02-26/KTU/01-CWL',
                'nama' => 'Iqbal Rupawan',
                'departemen' => 'Inti',
                'jabatan' => 'Ketua Umum',
                'ttl' => '2006-10-05',
                'angkatan' => '2024',
                'nim' => '24302043',
                'is_bph' => true,
                'email' => 'iqbal@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/WKTU/02-CWL',
                'nama' => 'Muhammad Faisal',
                'departemen' => 'Inti',
                'jabatan' => 'Wakil Ketua Umum',
                'ttl' => '2006-04-13',
                'angkatan' => '2025',
                'nim' => '25302008',
                'is_bph' => true,
                'email' => 'faisal@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/SKU1/03-CWL',
                'nama' => 'Rofiatul Husna',
                'departemen' => 'Inti',
                'jabatan' => 'Sekretaris Umum I',
                'ttl' => '2007-04-06',
                'angkatan' => '2025',
                'nim' => '25302029',
                'is_bph' => true,
                'email' => 'rofiatul@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/SKU2/04-CWL',
                'nama' => 'Seftia Hadliani',
                'departemen' => 'Inti',
                'jabatan' => 'Sekretaris Umum II',
                'ttl' => '2006-05-09',
                'angkatan' => '2024',
                'nim' => '24302059',
                'is_bph' => true,
                'email' => 'seftia@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/BDR1/05-CWL',
                'nama' => 'Fatimmatuzahra',
                'departemen' => 'Inti',
                'jabatan' => 'Bendahara Umum I',
                'ttl' => '2007-01-29',
                'angkatan' => '2025',
                'nim' => '25302040',
                'is_bph' => true,
                'email' => 'fatimmatuzahra@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/BDR2/06-CWL',
                'nama' => 'Halimatus Sa’diah',
                'departemen' => 'Inti',
                'jabatan' => 'Bendahara Umum II',
                'ttl' => '2005-06-12',
                'angkatan' => '2024',
                'nim' => '24302017',
                'is_bph' => true,
                'email' => 'halimatus@himati.id',
            ],

            // --- Media & Informasi (Medinfo) ---
            [
                'nrp' => 'HMTI/02-26/CO/D-MDI/07-CWL',
                'nama' => 'Muhammad Wahyu Saputra',
                'departemen' => 'Medinfo',
                'jabatan' => 'Koordinator',
                'ttl' => '2006-07-31',
                'angkatan' => '2024',
                'nim' => '24302045',
                'is_bph' => true,
                'email' => 'wahyu@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/08-CWL',
                'nama' => 'A. Nadil Ulum Annafis',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2006-05-31',
                'angkatan' => '2025',
                'nim' => '25302033',
                'is_bph' => false,
                'email' => 'nadil@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/09-CWL',
                'nama' => 'Muhammad Abdi',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2005-11-10',
                'angkatan' => '2024',
                'nim' => '24302057',
                'is_bph' => false,
                'email' => 'abdi@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/10-CWL',
                'nama' => 'Muhammad Rofie',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2006-03-29',
                'angkatan' => '2025',
                'nim' => '25302053',
                'is_bph' => false,
                'email' => 'rofie@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/11-CWL',
                'nama' => 'Noor Syarifah Putri Azzahra',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2007-01-02',
                'angkatan' => '2025',
                'nim' => '25302030',
                'is_bph' => false,
                'email' => 'syarifah@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/12-CWL',
                'nama' => 'Siti Khadijah',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2007-04-02',
                'angkatan' => '2025',
                'nim' => '25302039',
                'is_bph' => false,
                'email' => 'khadijah@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/13-CWL',
                'nama' => 'Isya Widiyana',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2007-01-17',
                'angkatan' => '2025',
                'nim' => '25302037',
                'is_bph' => false,
                'email' => 'isya@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/14-CWL',
                'nama' => 'Siti Aisyah',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2006-09-28',
                'angkatan' => '2025',
                'nim' => '25302026',
                'is_bph' => false,
                'email' => 'aisyah@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/15-CWL',
                'nama' => 'Sari Mulyani',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2006-12-18',
                'angkatan' => '2025',
                'nim' => '25302010',
                'is_bph' => false,
                'email' => 'sari@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-MDI/16-CWL',
                'nama' => 'Puja Dwi Anggreini',
                'departemen' => 'Medinfo',
                'jabatan' => 'Staff',
                'ttl' => '2005-08-12',
                'angkatan' => '2025',
                'nim' => '25302038',
                'is_bph' => false,
                'email' => 'puja@himati.id',
            ],

            // --- Pengembangan Sumber Daya Manusia (PSDM) ---
            [
                'nrp' => 'HMTI/02-26/CO/D-PSDM/17-CWL',
                'nama' => 'Muhammad Arif',
                'departemen' => 'PSDM',
                'jabatan' => 'Koordinator',
                'ttl' => '2005-01-02',
                'angkatan' => '2024',
                'nim' => '24302026',
                'is_bph' => true,
                'email' => 'arif@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-PSDM/18-CWL',
                'nama' => 'Mu’min Yasin',
                'departemen' => 'PSDM',
                'jabatan' => 'Staff',
                'ttl' => '2007-04-02',
                'angkatan' => '2025',
                'nim' => '25302011',
                'is_bph' => false,
                'email' => 'mumin@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-PSDM/20-CWL',
                'nama' => 'Nazwa Nur Rahma',
                'departemen' => 'PSDM',
                'jabatan' => 'Staff',
                'ttl' => '2007-07-24',
                'angkatan' => '2025',
                'nim' => '25302001',
                'is_bph' => false,
                'email' => 'nazwa@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-PSDM/21-CWL',
                'nama' => 'Fitria',
                'departemen' => 'PSDM',
                'jabatan' => 'Staff',
                'ttl' => '2007-03-31',
                'angkatan' => '2025',
                'nim' => '25302007',
                'is_bph' => false,
                'email' => 'fitria@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-PSDM/22-CWL',
                'nama' => 'Meidina Nur Aulia',
                'departemen' => 'PSDM',
                'jabatan' => 'Staff',
                'ttl' => '2007-08-15',
                'angkatan' => '2025',
                'nim' => '25302036',
                'is_bph' => false,
                'email' => 'meidina@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-PSDM/23-CWL',
                'nama' => 'Raudhatul Hasanah',
                'departemen' => 'PSDM',
                'jabatan' => 'Staff',
                'ttl' => '2006-05-02',
                'angkatan' => '2024',
                'nim' => '24302008',
                'is_bph' => false,
                'email' => 'raudhatul@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-PSDM/24-CWL',
                'nama' => 'Barkah',
                'departemen' => 'PSDM',
                'jabatan' => 'Staff',
                'ttl' => '2006-10-19',
                'angkatan' => '2024',
                'nim' => '24302007',
                'is_bph' => false,
                'email' => 'barkah@himati.id',
            ],

            // --- Industri Kreatif (Industif / IKA) ---
            [
                'nrp' => 'HMTI/02-26/CO/D-IKA/26-CWL',
                'nama' => 'Aidul Akbari',
                'departemen' => 'Industif',
                'jabatan' => 'Koordinator',
                'ttl' => '2007-02-01',
                'angkatan' => '2024',
                'nim' => '24302013',
                'is_bph' => true,
                'email' => 'aidul@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-IKA/27-CWL',
                'nama' => 'Muhammad Hidayat',
                'departemen' => 'Industif',
                'jabatan' => 'Staff',
                'ttl' => '2005-02-12',
                'angkatan' => '2024',
                'nim' => '24302031',
                'is_bph' => false,
                'email' => 'hidayat@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-IKA/28-CWL',
                'nama' => 'Muhammad Amin',
                'departemen' => 'Industif',
                'jabatan' => 'Staff',
                'ttl' => null,
                'angkatan' => '2024',
                'nim' => '24302012',
                'is_bph' => false,
                'email' => 'amin@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-IKA/29-CWL',
                'nama' => 'Anida Rahmah',
                'departemen' => 'Industif',
                'jabatan' => 'Staff',
                'ttl' => '2007-06-13',
                'angkatan' => '2025',
                'nim' => '25302020',
                'is_bph' => false,
                'email' => 'anida@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-IKA/31-CWL',
                'nama' => 'Sururia Mahmudah',
                'departemen' => 'Industif',
                'jabatan' => 'Staff',
                'ttl' => '2007-04-21',
                'angkatan' => '2025',
                'nim' => '25302041',
                'is_bph' => false,
                'email' => 'sururia@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-IKA/32-CWL',
                'nama' => 'Raudatul Jannah',
                'departemen' => 'Industif',
                'jabatan' => 'Staff',
                'ttl' => '2006-05-05',
                'angkatan' => '2024',
                'nim' => '24302044',
                'is_bph' => false,
                'email' => 'jannah@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-IKA/33-CWL',
                'nama' => 'Farras Dwie Cahyani',
                'departemen' => 'Industif',
                'jabatan' => 'Staff',
                'ttl' => '2006-01-17',
                'angkatan' => '2024',
                'nim' => '24302032',
                'is_bph' => false,
                'email' => 'farras@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-IKA/34-CWL',
                'nama' => 'Risnawati',
                'departemen' => 'Industif',
                'jabatan' => 'Staff',
                'ttl' => '2003-04-01',
                'angkatan' => '2024',
                'nim' => '24302003',
                'is_bph' => false,
                'email' => 'risnawati@himati.id',
            ],

            // --- Afiliasi dan Kerja Sama (Afkers / AKS) ---
            [
                'nrp' => 'HMTI/02-26/CO/D-AKS/35-CWL',
                'nama' => 'Noor Hidayah',
                'departemen' => 'Afkers',
                'jabatan' => 'Koordinator',
                'ttl' => '2005-01-11',
                'angkatan' => '2024',
                'nim' => '24302029',
                'is_bph' => true,
                'email' => 'noor.hidayah@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/36-CWL',
                'nama' => 'Anton Maulana',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2006-01-04',
                'angkatan' => '2025',
                'nim' => '25302031',
                'is_bph' => false,
                'email' => 'anton@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/37-CWL',
                'nama' => 'Muhammad Basit',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2006-10-30',
                'angkatan' => '2025',
                'nim' => '25302005',
                'is_bph' => false,
                'email' => 'basit@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/38-CWL',
                'nama' => 'Eva Widya Safitri',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2006-11-29',
                'angkatan' => '2025',
                'nim' => '25302002',
                'is_bph' => false,
                'email' => 'eva@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/39-CWL',
                'nama' => 'Salsa Agustina',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2006-08-22',
                'angkatan' => '2025',
                'nim' => '25302047',
                'is_bph' => false,
                'email' => 'salsa@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/40-CWL',
                'nama' => 'Nabila Carolina Puteri',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2007-05-04',
                'angkatan' => '2025',
                'nim' => '25302027',
                'is_bph' => false,
                'email' => 'carolina@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/41-CWL',
                'nama' => 'Sandriana Ariani',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2006-02-10',
                'angkatan' => '2024',
                'nim' => '24302004',
                'is_bph' => false,
                'email' => 'sandriana@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/42-CWL',
                'nama' => 'Kharunnisa',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2005-01-07',
                'angkatan' => '2024',
                'nim' => '24302030',
                'is_bph' => false,
                'email' => 'kharunnisa@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/43-CWL',
                'nama' => 'Muhammad Amin Badali',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2005-10-08',
                'angkatan' => '2024',
                'nim' => '24302015',
                'is_bph' => false,
                'email' => 'badali@himati.id',
            ],
            [
                'nrp' => 'HMTI/02-26/D-AKS/44-CWL',
                'nama' => 'Siti Maryam',
                'departemen' => 'Afkers',
                'jabatan' => 'Staff',
                'ttl' => '2005-08-09',
                'angkatan' => '2024',
                'nim' => '24302005',
                'is_bph' => false,
                'email' => 'maryam@himati.id',
            ],
        ];

        $qrService = app(QrCodeService::class);
        $totalInserted = 0;

        foreach ($membersData as $data) {
            $member = Member::create([
                'nama_lengkap' => $data['nama'],
                'departemen' => $data['departemen'],
                'jabatan' => $data['jabatan'],
                'nim' => $data['nim'],
                'nrp' => $data['nrp'],
                'angkatan' => $data['angkatan'],
                'ttl' => $data['ttl'],
                'email' => $data['email'],
                'password' => $defaultPassword,
                'qr_code' => $data['nrp'],
                'is_bph' => $data['is_bph'],
                'is_active' => true,
                'sp_level' => 0,
                'sp_base_level' => 0,
            ]);

            // Generate file gambar QR Code untuk masing-masing pengurus
            $qrService->ensureMemberQr($member, true);
            $totalInserted++;
        }

        $this->command->info("Berhasil mereset sistem dan memasukkan {$totalInserted} data pengurus baru lengkap dengan QR Code!");
    }
}
