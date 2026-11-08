<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $filterTipe = $request->query('tipe');
        $filterStatus = $request->query('status');

        $query = Announcement::with(['admin', 'targetUser', 'reads'])
            ->orderBy('pinned', 'desc')
            ->orderBy('created_at', 'desc');

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('konten', 'like', "%{$search}%");
            });
        }

        if (filled($filterTipe)) {
            $query->where('tipe', $filterTipe);
        }

        if ($filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        $announcements = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Announcement::count(),
            'active' => Announcement::where('is_active', true)->count(),
            'pinned' => Announcement::where('pinned', true)->count(),
        ];

        $members = Member::orderBy('departemen')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'departemen', 'jabatan', 'is_bph']);

        $departments = Member::distinct()
            ->pluck('departemen')
            ->filter()
            ->values()
            ->all();

        if (! in_array('BPH', $departments, true)) {
            $departments[] = 'BPH';
        }

        return view('announcements.index', compact('announcements', 'stats', 'members', 'departments', 'search', 'filterTipe', 'filterStatus'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'konten' => ['required', 'string'],
            'tipe' => ['required', 'in:info,penting,mendesak'],
            'target_type' => ['required', 'in:all,departemen,personal'],
            'target_departemen' => ['nullable', 'string', 'max:100'],
            'target_user_id' => ['nullable', 'exists:users,id'],
            'is_banner' => ['nullable', 'boolean'],
            'pinned' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if ($validated['target_type'] === 'departemen' && empty($validated['target_departemen'])) {
            return back()->withInput()->withErrors(['target_departemen' => 'Pilih departemen tujuan pengumuman.']);
        }

        if ($validated['target_type'] === 'personal' && empty($validated['target_user_id'])) {
            return back()->withInput()->withErrors(['target_user_id' => 'Pilih anggota penerima pesan notifikasi.']);
        }

        Announcement::create([
            'admin_id' => auth()->id(),
            'judul' => $validated['judul'],
            'konten' => $validated['konten'],
            'tipe' => $validated['tipe'],
            'target_type' => $validated['target_type'],
            'target_departemen' => $validated['target_type'] === 'departemen' ? $validated['target_departemen'] : null,
            'target_user_id' => $validated['target_type'] === 'personal' ? $validated['target_user_id'] : null,
            'is_banner' => $request->boolean('is_banner', true),
            'is_active' => true,
            'pinned' => $request->boolean('pinned', false),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Pengumuman / notifikasi berhasil dibuat dan dipublikasikan.');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'konten' => ['required', 'string'],
            'tipe' => ['required', 'in:info,penting,mendesak'],
            'target_type' => ['required', 'in:all,departemen,personal'],
            'target_departemen' => ['nullable', 'string', 'max:100'],
            'target_user_id' => ['nullable', 'exists:users,id'],
            'is_banner' => ['nullable', 'boolean'],
            'pinned' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $announcement->update([
            'judul' => $validated['judul'],
            'konten' => $validated['konten'],
            'tipe' => $validated['tipe'],
            'target_type' => $validated['target_type'],
            'target_departemen' => $validated['target_type'] === 'departemen' ? $validated['target_departemen'] : null,
            'target_user_id' => $validated['target_type'] === 'personal' ? $validated['target_user_id'] : null,
            'is_banner' => $request->boolean('is_banner'),
            'pinned' => $request->boolean('pinned'),
            'is_active' => $request->boolean('is_active', true),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function toggleStatus(Announcement $announcement): RedirectResponse
    {
        $newStatus = ! $announcement->is_active;
        $announcement->update(['is_active' => $newStatus]);

        $message = $newStatus ? 'Pengumuman telah diaktifkan.' : 'Pengumuman telah dinonaktifkan.';

        return back()->with('success', $message);
    }

    public function togglePin(Announcement $announcement): RedirectResponse
    {
        $newPinned = ! $announcement->pinned;
        $announcement->update(['pinned' => $newPinned]);

        $message = $newPinned ? 'Pengumuman berhasil disematkan di posisi teratas.' : 'Pin pengumuman berhasil dicopot.';

        return back()->with('success', $message);
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }
}
