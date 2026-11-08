<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\QrCodeService;
use App\Services\SpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    protected QrCodeService $qrService;

    protected SpService $spService;

    public function __construct(QrCodeService $qrService, SpService $spService)
    {
        $this->qrService = $qrService;
        $this->spService = $spService;
    }

    public function index(Request $request)
    {
        $dept = $request->query('dept');
        $search = $request->query('q');
        $sp = $request->query('sp');

        $query = Member::query();

        if ($dept) {
            if ($dept === 'BPH') {
                $query->where(function ($q) {
                    $q->where('departemen', 'Inti')
                        ->orWhere('is_bph', true);
                });
            } else {
                $query->where('departemen', $dept);
            }
        }

        if ($sp !== null && $sp !== '') {
            if ($sp === 'any') {
                $query->where('sp_level', '>', 0);
            } elseif (in_array((string) $sp, ['0', '1', '2', '3'], true)) {
                $query->where('sp_level', (int) $sp);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('qr_code', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('departemen')
            ->orderBy('nama_lengkap')
            ->paginate(20)
            ->withQueryString();

        $departments = config('himati.departments', []);

        return view('members.index', compact('members', 'dept', 'search', 'sp', 'departments'));
    }

    public function create()
    {
        $departments = config('himati.departments', []);

        return view('members.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'departemen' => ['required', 'string', 'max:50'],
            'jabatan' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'min:8'],
            'is_bph' => ['boolean'],
        ]);

        // Generate unique QR code string: HIMATI-USR-[random]
        $uniqueSuffix = strtoupper(Str::random(6));
        $qrCode = 'HIMATI-USR-'.$uniqueSuffix;

        $accountPassword = $validated['password']
            ?? (filled($validated['email'] ?? null) ? config('himati.default_member_password') : null);

        $member = Member::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'departemen' => $validated['departemen'],
            'jabatan' => $validated['jabatan'],
            'email' => $validated['email'] ?? null,
            'password' => $accountPassword,
            'qr_code' => $qrCode,
            'is_bph' => $request->boolean('is_bph'),
        ]);

        // Auto generate QR image
        $this->qrService->ensureMemberQr($member, true);

        $accountMessage = '';
        if ($member->hasAccount()) {
            $accountMessage = filled($validated['password'] ?? null)
                ? ' Akun login berhasil dibuat.'
                : ' Akun login dibuat dengan password awal: '.config('himati.default_member_password').'.';
        }

        return redirect()->route('members.index')
            ->with('success', 'Anggota '.$member->nama_lengkap.' berhasil ditambahkan dan QR code telah dibuat.'.$accountMessage);
    }

    public function edit($id)
    {
        $member = Member::findOrFail($id);
        $departments = config('himati.departments', []);

        return view('members.edit', compact('member', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'departemen' => ['required', 'string', 'max:50'],
            'jabatan' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($member->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_bph' => ['boolean'],
            'sp_level' => ['nullable', 'integer', 'in:0,1,2,3'],
            'sp_reason' => ['nullable', 'string', 'max:255'],
            'sp_detail' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'departemen' => $validated['departemen'],
            'jabatan' => $validated['jabatan'],
            'email' => $validated['email'] ?? null,
            'is_bph' => $request->boolean('is_bph'),
        ];

        if ($request->boolean('reset_password_to_default')) {
            $data['password'] = config('himati.default_member_password', 'password123');
        } elseif (filled($validated['password'] ?? null)) {
            $data['password'] = $validated['password'];
        }

        $member->update($data);

        if ($request->has('sp_level')) {
            $targetSp = (int) $request->input('sp_level');
            $newReason = trim((string) $request->input('sp_reason'));
            $newDetail = trim((string) $request->input('sp_detail'));
            if (
                $targetSp !== (int) $member->sp_level
                || $newReason !== trim((string) $member->sp_reason)
                || $newDetail !== trim((string) $member->sp_detail)
            ) {
                $this->spService->setSpByAdmin($member, $targetSp, $newReason, $newDetail);
            }
        }

        // Re-generate QR image with updated metadata
        $this->qrService->ensureMemberQr($member, true);

        return redirect()->route('members.index')
            ->with('success', 'Data anggota '.$member->nama_lengkap.' berhasil diperbarui.');
    }

    public function addSp(Request $request, Member $member)
    {
        if ((int) $member->sp_level >= 3) {
            return back()->with('warning', "{$member->nama_lengkap} sudah berstatus SP 3 (Diberhentikan & Nonaktif).");
        }

        $reason = $request->input('sp_reason') ?: $request->input('reason');
        $detail = $request->input('sp_detail') ?: $request->input('detail');
        $this->spService->addSpByAdmin($member, $reason, $detail);

        if ((int) $member->sp_level >= 3) {
            return back()->with('warning', "SP 3 (Pemberhentian) telah diberikan kepada {$member->nama_lengkap}. Akun pengurus telah dinonaktifkan.");
        }

        return back()->with('success', "Status {$member->nama_lengkap} berhasil diberikan {$member->sp_label}.");
    }

    public function removeSp(Member $member)
    {
        $this->spService->removeSpByAdmin($member);

        return back()->with('success', "Status SP {$member->nama_lengkap} berhasil dihapus dan akun kembali aktif (Aman).");
    }

    public function setSp(Request $request, Member $member)
    {
        $validated = $request->validate([
            'sp_level' => ['required', 'integer', 'in:0,1,2,3'],
            'sp_reason' => ['nullable', 'string', 'max:255'],
            'sp_detail' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->spService->setSpByAdmin(
            $member,
            (int) $validated['sp_level'],
            $validated['sp_reason'] ?? null,
            $validated['sp_detail'] ?? null
        );

        if ((int) $member->sp_level === 0) {
            return back()->with('success', "Status SP {$member->nama_lengkap} berhasil dihapus (Aman).");
        }

        return back()->with('success', "Status SP {$member->nama_lengkap} berhasil diperbarui menjadi {$member->sp_label}.");
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $name = $member->nama_lengkap;

        // Delete QR image and profile photo files if they exist
        if ($member->qr_image) {
            $filepath = public_path('assets/img/qrcodes/'.$member->qr_image);
            if (File::exists($filepath)) {
                File::delete($filepath);
            }
        }

        if ($member->profile_photo) {
            $filepath = public_path('uploads/profiles/'.$member->profile_photo);
            if (File::exists($filepath)) {
                File::delete($filepath);
            }
        }

        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Anggota '.$name.' berhasil dihapus.');
    }

    public function generateQrIndex()
    {
        $members = Member::orderBy('departemen')->orderBy('nama_lengkap')->get();

        return view('members.generate_qr', compact('members'));
    }

    public function generateAllQr()
    {
        $count = $this->qrService->generateAll();

        return back()->with('success', "Berhasil mengenerate {$count} QR Code anggota.");
    }

    public function downloadQrZip()
    {
        $zipPath = $this->qrService->createZipArchive();
        if (! $zipPath || ! File::exists($zipPath)) {
            return back()->with('danger', 'Gagal membuat file ZIP QR code.');
        }

        return response()->download($zipPath, 'QR_Codes_HIMATI_'.date('Ymd').'.zip')->deleteFileAfterSend(true);
    }

    public function downloadSingleQr($id)
    {
        $member = Member::findOrFail($id);
        $filename = $this->qrService->ensureMemberQr($member);

        if (! $filename) {
            return back()->with('danger', 'Gagal membuat file QR Code.');
        }

        $filepath = public_path('assets/img/qrcodes/'.$filename);
        $downloadName = 'QR_'.Str::slug($member->nama_lengkap).'_'.$member->qr_code.'.png';

        return response()->download($filepath, $downloadName);
    }

    public function myQr(Request $request)
    {
        $search = $request->query('q');
        $member = null;

        if ($search) {
            $member = Member::where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('email', $search)
                ->orWhere('qr_code', $search)
                ->first();

            if ($member) {
                $this->qrService->ensureMemberQr($member);
            }
        }

        $allMembers = Member::orderBy('nama_lengkap')->select('id', 'nama_lengkap', 'departemen', 'jabatan')->get();

        return view('members.my_qr', compact('member', 'search', 'allMembers'));
    }
}
