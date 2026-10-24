<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    protected QrCodeService $qrService;

    public function __construct(QrCodeService $qrService)
    {
        $this->qrService = $qrService;
    }

    public function index(Request $request)
    {
        $dept = $request->query('dept');
        $search = $request->query('q');

        $query = Member::query();

        if ($dept) {
            $query->where('departemen', $dept);
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
            ->paginate(20);

        $departments = config('himati.departments', []);

        return view('members.index', compact('members', 'dept', 'search', 'departments'));
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
            'departemen'   => ['required', 'string', 'max:50'],
            'jabatan'      => ['required', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:100'],
            'is_bph'       => ['boolean'],
        ]);

        // Generate unique QR code string: HIMATI-USR-[random]
        $uniqueSuffix = strtoupper(Str::random(6));
        $qrCode = 'HIMATI-USR-' . $uniqueSuffix;

        $member = Member::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'departemen'   => $validated['departemen'],
            'jabatan'      => $validated['jabatan'],
            'email'        => $validated['email'],
            'qr_code'      => $qrCode,
            'is_bph'       => $request->boolean('is_bph'),
        ]);

        // Auto generate QR image
        $this->qrService->ensureMemberQr($member, true);

        return redirect()->route('members.index')
            ->with('success', 'Anggota ' . $member->nama_lengkap . ' berhasil ditambahkan dan QR code telah dibuat.');
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
            'departemen'   => ['required', 'string', 'max:50'],
            'jabatan'      => ['required', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:100'],
            'is_bph'       => ['boolean'],
        ]);

        $member->update([
            'nama_lengkap' => $validated['nama_lengkap'],
            'departemen'   => $validated['departemen'],
            'jabatan'      => $validated['jabatan'],
            'email'        => $validated['email'],
            'is_bph'       => $request->boolean('is_bph'),
        ]);

        // Re-generate QR image with updated metadata
        $this->qrService->ensureMemberQr($member, true);

        return redirect()->route('members.index')
            ->with('success', 'Data anggota ' . $member->nama_lengkap . ' berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $name = $member->nama_lengkap;

        // Delete QR image file if exists
        if ($member->qr_image) {
            $filepath = public_path('assets/img/qrcodes/' . $member->qr_image);
            if (File::exists($filepath)) {
                File::delete($filepath);
            }
        }

        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Anggota ' . $name . ' berhasil dihapus.');
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
        if (!$zipPath || !File::exists($zipPath)) {
            return back()->with('danger', 'Gagal membuat file ZIP QR code.');
        }

        return response()->download($zipPath, 'QR_Codes_HIMATI_' . date('Ymd') . '.zip')->deleteFileAfterSend(true);
    }

    public function downloadSingleQr($id)
    {
        $member = Member::findOrFail($id);
        $filename = $this->qrService->ensureMemberQr($member);

        if (!$filename) {
            return back()->with('danger', 'Gagal membuat file QR Code.');
        }

        $filepath = public_path('assets/img/qrcodes/' . $filename);
        $downloadName = 'QR_' . Str::slug($member->nama_lengkap) . '_' . $member->qr_code . '.png';

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
