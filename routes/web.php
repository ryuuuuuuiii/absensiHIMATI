<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PublicAttendanceController;
use App\Http\Controllers\PublicMeetingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// ============================================
// ROOT REDIRECT
// ============================================
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('public.meeting.create');
});

// ============================================
// PUBLIC ROUTES
// ============================================
Route::get('/rapat', [PublicMeetingController::class, 'index'])->name('public.meeting.create');
Route::post('/rapat', [PublicMeetingController::class, 'store'])->name('public.meeting.store');
Route::get('/rapat/{unique_link}/hasil', [PublicMeetingController::class, 'hasil'])->name('public.hasil');
Route::get('/rapat/{unique_link}/scanner', [PublicMeetingController::class, 'scanner'])->name('public.scanner');

Route::get('/rapat/{unique_link}/online', [PublicAttendanceController::class, 'onlineAttend'])->name('public.online');
Route::post('/rapat/{unique_link}/online', [PublicAttendanceController::class, 'submitOnlineAttend'])->name('public.online.submit');

Route::get('/rapat/{absent_link}/absent', [PublicAttendanceController::class, 'absentForm'])->name('public.absent');
Route::post('/rapat/{absent_link}/absent', [PublicAttendanceController::class, 'submitAbsentForm'])->name('public.absent.submit');

// Short URL aliases
Route::get('/scan/{unique_link}', [PublicMeetingController::class, 'scanner']);
Route::get('/attend/{unique_link}', [PublicAttendanceController::class, 'onlineAttend']);
Route::get('/absent/{absent_link}', [PublicAttendanceController::class, 'absentForm']);
Route::get('/hasil/{unique_link}', [PublicMeetingController::class, 'hasil']);

// Public Member QR Lookup
Route::get('/my-qr', [MemberController::class, 'myQr'])->name('public.my_qr');
Route::get('/my-qr/{id}/download', [MemberController::class, 'downloadSingleQr'])->name('public.my_qr.download');

// ============================================
// AUTHENTICATION
// ============================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============================================
// ADMIN PROTECTED ROUTES
// ============================================
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Meetings
    Route::prefix('meetings')->name('meetings.')->group(function () {
        Route::get('/', [MeetingController::class, 'index'])->name('index');
        Route::get('/create', [MeetingController::class, 'create'])->name('create');
        Route::post('/', [MeetingController::class, 'store'])->name('store');
        Route::get('/{meeting}', [MeetingController::class, 'show'])->name('show');
        Route::get('/{meeting}/scan', [MeetingController::class, 'scan'])->name('scan');
        Route::post('/{meeting}/scan', [MeetingController::class, 'processScan'])->name('process_scan');
        Route::post('/{meeting}/finish', [MeetingController::class, 'finish'])->name('finish');
        Route::post('/{meeting}/photo', [MeetingController::class, 'savePhoto'])->name('save_photo');
        Route::post('/{meeting}/sync-sheets', [MeetingController::class, 'syncSheets'])->name('sync_sheets');
        Route::post('/{meeting}/attendances/{attendance}', [MeetingController::class, 'updateAttendanceStatus'])->name('update_attendance');
        Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->name('destroy');
    });

    // API endpoints
    Route::get('/api/events/{event}/data', [MeetingController::class, 'getEventData'])->name('api.events.data');
    Route::get('/meeting/process/get_event_data.php', [MeetingController::class, 'getEventData']);

    // Members
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('/create', [MemberController::class, 'create'])->name('create');
        Route::post('/', [MemberController::class, 'store'])->name('store');
        Route::get('/generate-qr', [MemberController::class, 'generateQrIndex'])->name('generate_qr');
        Route::post('/generate-qr/all', [MemberController::class, 'generateAllQr'])->name('generate_qr.all');
        Route::get('/download-qr-zip', [MemberController::class, 'downloadQrZip'])->name('download_qr_zip');
        Route::get('/{member}/download-qr', [MemberController::class, 'downloadSingleQr'])->name('download_qr_single');
        Route::get('/{member}/edit', [MemberController::class, 'edit'])->name('edit');
        Route::put('/{member}', [MemberController::class, 'update'])->name('update');
        Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy');
    });

    // Events
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/{event}', [EventController::class, 'show'])->name('show');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::post('/{event}/archive', [EventController::class, 'archive'])->name('archive');
        Route::post('/{event}/restore', [EventController::class, 'restore'])->name('restore');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');

        // Divisions
        Route::post('/{event}/divisions', [EventController::class, 'addDivision'])->name('divisions.store');
        Route::put('/{event}/divisions/{division}', [EventController::class, 'updateDivision'])->name('divisions.update');
        Route::delete('/{event}/divisions/{division}', [EventController::class, 'deleteDivision'])->name('divisions.destroy');

        // Panitia
        Route::post('/{event}/members', [EventController::class, 'addMembers'])->name('members.store');
        Route::delete('/{event}/members/{member}', [EventController::class, 'removeMember'])->name('members.destroy');
    });

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/general', [SettingController::class, 'updateGeneral'])->name('general');
        Route::post('/google', [SettingController::class, 'updateGoogle'])->name('google');
        Route::post('/logo', [SettingController::class, 'uploadLogo'])->name('logo');
    });
});

// ============================================
// LEGACY COMPATIBILITY ROUTES
// ============================================
Route::post('/meeting/process/process_scan.php', [MeetingController::class, 'processScanLegacy']);
Route::post('/meeting/process/save_photo.php', [MeetingController::class, 'savePhotoLegacy']);
Route::get('/meeting/detail.php', function () {
    return redirect()->route('meetings.show', request('id'));
});
Route::get('/meeting/scan.php', function () {
    return redirect()->route('meetings.scan', request('id'));
});
Route::get('/rapat/scanner.php', function () {
    return redirect()->route('public.scanner', request('token'));
});
Route::get('/rapat/hasil.php', function () {
    return redirect()->route('public.hasil', request('token'));
});
Route::get('/meeting/absent_form.php', function () {
    return redirect()->route('public.absent', request('token'));
});
Route::get('/meeting/online_attend.php', function () {
    return redirect()->route('public.online', request('token'));
});

