<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MemberAccountController;
use App\Http\Controllers\MemberAuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PublicAttendanceController;
use App\Http\Controllers\PublicMeetingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Models\Meeting;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Route;

// ============================================
// ROOT REDIRECT
// ============================================
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    if (auth('member')->check()) {
        return redirect()->route('portal.dashboard');
    }

    return redirect()->route('login');
});

// ============================================
// MEETING CREATION (Wajib Akun: Admin / Pengurus)
// ============================================
Route::middleware('auth:web,member')->group(function () {
    Route::get('/rapat', [PublicMeetingController::class, 'index'])->name('public.meeting.create');
    Route::post('/rapat', [PublicMeetingController::class, 'store'])->name('public.meeting.store');
});

// ============================================
// PUBLIC ATTENDANCE & SCANNER ROUTES
// ============================================
Route::get('/rapat/{unique_link}/hasil', [PublicMeetingController::class, 'hasil'])->name('public.hasil');
Route::post('/rapat/{unique_link}/finish', [PublicMeetingController::class, 'finish'])->name('public.meeting.finish');
Route::post('/rapat/{unique_link}/cancel', [PublicMeetingController::class, 'cancel'])->name('public.meeting.cancel');
Route::post('/rapat/{unique_link}/update-expiry', [PublicMeetingController::class, 'updateExpiry'])->name('public.meeting.update_expiry');
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

// Event data API (for dynamic meeting form)
Route::get('/api/events/{event}/data', [MeetingController::class, 'getEventData'])->name('api.events.data');
Route::get('/meeting/process/get_event_data.php', [MeetingController::class, 'getEventData']);

// ============================================
// AUTHENTICATION
// ============================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============================================
// MEMBER PORTAL (PORTAL PENGURUS)
// ============================================
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [MemberAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [MemberAuthController::class, 'login'])->name('login.submit');

    Route::middleware('auth:member')->group(function () {
        Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');

        Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/riwayat', [PortalController::class, 'attendance'])->name('attendance');
        Route::get('/qr', [PortalController::class, 'qr'])->name('qr');
        Route::get('/qr/download', [PortalController::class, 'downloadQr'])->name('qr.download');
        Route::get('/absen', [PortalController::class, 'absenceForm'])->name('absence.create');
        Route::post('/absen', [PortalController::class, 'submitAbsence'])->name('absence.store');
        Route::post('/presensi/{attendance}', [PortalController::class, 'submitPresence'])->name('presence.store');
        Route::get('/profil', [PortalController::class, 'profile'])->name('profile');
        Route::put('/profil', [PortalController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profil/password', [PortalController::class, 'updatePassword'])->name('profile.password');
        Route::post('/feedback', [PortalController::class, 'storeFeedback'])->name('feedback.store');

        // Notifikasi & Pengumuman Admin
        Route::prefix('notifikasi')->name('announcements.')->group(function () {
            Route::get('/', [PortalController::class, 'announcements'])->name('index');
            Route::post('/{announcement}/read', [PortalController::class, 'readAnnouncement'])->name('read');
            Route::post('/{announcement}/dismiss', [PortalController::class, 'dismissAnnouncement'])->name('dismiss');
            Route::post('/read-all', [PortalController::class, 'readAllAnnouncements'])->name('read_all');
        });
    });
});

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
        Route::post('/{meeting}/cancel', [MeetingController::class, 'cancel'])->name('cancel');
        Route::post('/{meeting}/photo', [MeetingController::class, 'savePhoto'])->name('save_photo');
        Route::post('/{meeting}/sync-sheets', [MeetingController::class, 'syncSheets'])->name('sync_sheets');
        Route::post('/{meeting}/attendances/{attendance}', [MeetingController::class, 'updateAttendanceStatus'])->name('update_attendance');
        Route::post('/{meeting}/update-expiry', [MeetingController::class, 'updateExpiry'])->name('update_expiry');
        Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->name('destroy');
    });

    // Members
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('/create', [MemberController::class, 'create'])->name('create');
        Route::post('/', [MemberController::class, 'store'])->name('store');
        Route::get('/generate-qr', [MemberController::class, 'generateQrIndex'])->name('generate_qr');
        Route::post('/generate-qr/all', [MemberController::class, 'generateAllQr'])->name('generate_qr.all');
        Route::get('/download-qr-zip', [MemberController::class, 'downloadQrZip'])->name('download_qr_zip');

        // Member login accounts
        Route::get('/accounts', [MemberAccountController::class, 'index'])->name('accounts');
        Route::post('/accounts/generate', [MemberAccountController::class, 'generateMissing'])->name('accounts.generate');
        Route::post('/accounts/{member}/reset-password', [MemberAccountController::class, 'resetPassword'])->name('accounts.reset');
        Route::post('/accounts/{member}/disable', [MemberAccountController::class, 'disable'])->name('accounts.disable');

        // Surat Peringatan (SP 1 - SP 3)
        Route::post('/{member}/sp/add', [MemberController::class, 'addSp'])->name('sp.add');
        Route::post('/{member}/sp/remove', [MemberController::class, 'removeSp'])->name('sp.remove');
        Route::post('/{member}/sp/set', [MemberController::class, 'setSp'])->name('sp.set');

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

    // Feedbacks
    Route::prefix('feedbacks')->name('feedbacks.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::put('/{feedback}', [FeedbackController::class, 'update'])->name('update');
        Route::delete('/{feedback}', [FeedbackController::class, 'destroy'])->name('destroy');
    });

    // Announcements & Messages from Admin
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
        Route::post('/{announcement}/toggle-status', [AnnouncementController::class, 'toggleStatus'])->name('toggle_status');
        Route::post('/{announcement}/toggle-pin', [AnnouncementController::class, 'togglePin'])->name('toggle_pin');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/general', [SettingController::class, 'updateGeneral'])->name('general');
        Route::post('/google', [SettingController::class, 'updateGoogle'])->name('google');
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

// ============================================
// UPLOADS FILE ROUTING (Support Vercel Serverless)
// ============================================
Route::get('/uploads/{folder}/{filename}', function (string $folder, string $filename) {
    $res = app(FileUploadService::class)->response($folder, $filename);
    if ($res) {
        return $res;
    }

    abort(404);
})->where('folder', 'profiles|bukti|dokumentasi|feedbacks|logos');
