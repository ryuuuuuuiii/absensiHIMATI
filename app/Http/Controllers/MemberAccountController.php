<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class MemberAccountController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');

        $query = Member::query();

        if ($search) {
            $query->where(function ($builder) use ($search) {
                $builder->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('departemen', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('departemen')
            ->orderBy('nama_lengkap')
            ->paginate(25)
            ->withQueryString();

        $defaultPassword = config('himati.default_member_password');
        $withoutAccount = Member::query()
            ->where('is_active', true)
            ->where('sp_level', '<', 3)
            ->where(function ($builder) {
                $builder->whereNull('password')->orWhere('password', '');
            })
            ->count();

        return view('members.accounts', compact('members', 'search', 'defaultPassword', 'withoutAccount'));
    }

    public function generateMissing()
    {
        $defaultPassword = config('himati.default_member_password');

        $members = Member::query()
            ->where('is_active', true)
            ->where('sp_level', '<', 3)
            ->where(function ($builder) {
                $builder->whereNull('password')->orWhere('password', '');
            })
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get();

        foreach ($members as $member) {
            $member->update(['password' => $defaultPassword]);
        }

        $count = $members->count();

        if ($count === 0) {
            return back()->with('info', 'Semua pengurus yang memiliki email sudah memiliki akun login.');
        }

        return back()->with('success', "{$count} akun login berhasil dibuat. Password awal: {$defaultPassword}. Minta pengurus segera mengganti password melalui Portal.");
    }

    public function resetPassword(Member $member)
    {
        if (blank($member->email)) {
            return back()->with('error', "Anggota {$member->nama_lengkap} belum memiliki email untuk login. Harap lengkapi email terlebih dahulu.");
        }

        $defaultPassword = config('himati.default_member_password', 'password123');

        $member->update(['password' => $defaultPassword]);

        return back()->with('success', "Password akun {$member->nama_lengkap} berhasil direset menjadi default: {$defaultPassword}.");
    }

    public function disable(Member $member)
    {
        $member->update(['password' => null]);

        return back()->with('success', "Akun login {$member->nama_lengkap} berhasil dinonaktifkan.");
    }
}
