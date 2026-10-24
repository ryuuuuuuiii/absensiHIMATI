<?php

return [
    'app_name' => 'Absensi HIMA TI',
    'app_full_name' => 'Absensi HIMA TI Politeknik Hasnur',
    'version' => '2.0.0',
    'timezone' => 'Asia/Makassar',

    'departments' => [
        'Inti'     => 'Departemen Inti',
        'PSDM'     => 'Departemen PSDM',
        'Medinfo'  => 'Departemen Medinfo',
        'Afkers'   => 'Departemen Afkers',
        'Industif' => 'Departemen Industif',
    ],

    'meeting_types' => [
        'internal_dept' => 'Rapat Internal Departemen',
        'perdana'       => 'Rapat Perdana',
        'kepanitiaan'   => 'Rapat Kepanitiaan / Even',
        'bph'           => 'Rapat BPH / Inti',
        'lintas_dept'   => 'Rapat Lintas Departemen',
    ],

    'attendance_statuses' => [
        'hadir' => 'Hadir',
        'izin'  => 'Izin',
        'sakit' => 'Sakit',
        'alpha' => 'Alpha',
    ],

    'max_photo_size' => 5 * 1024 * 1024, // 5MB
    'online_link_expiry_hours' => 3,
];
