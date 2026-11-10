<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMember;
use App\Models\Member;
use App\Services\GamificationService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $activeEvents = Event::withCount(['divisions', 'members', 'meetings'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $completedEvents = Event::withCount(['divisions', 'members', 'meetings'])
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get();

        $archivedEvents = Event::withCount(['divisions', 'members', 'meetings'])
            ->where('status', 'archived')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('events.index', compact('activeEvents', 'completedEvents', 'archivedEvents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_event' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $event = Event::create([
            'nama_event' => $validated['nama_event'],
            'deskripsi' => $validated['deskripsi'],
            'status' => 'active',
        ]);

        return redirect()->route('events.show', $event->id)
            ->with('success', 'Event '.$event->nama_event.' berhasil dibuat. Silakan tambahkan divisi dan panitia.');
    }

    public function show($id)
    {
        $event = Event::with([
            'divisions.members.member',
            'meetings' => function ($q) {
                $q->orderBy('tanggal', 'desc');
            },
        ])->findOrFail($id);

        $divisions = $event->divisions;

        $existingMemberIds = EventMember::where('event_id', $event->id)->pluck('user_id')->toArray();
        $availableMembers = Member::whereNotIn('id', $existingMemberIds)
            ->orderBy('departemen')
            ->orderBy('nama_lengkap')
            ->get();

        $allMembers = Member::orderBy('departemen')->orderBy('nama_lengkap')->get();

        return view('events.show', compact('event', 'divisions', 'availableMembers', 'allMembers'));
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'nama_event' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $event->update($validated);

        return back()->with('success', 'Detail event berhasil diperbarui.');
    }

    public function archive($id)
    {
        $event = Event::findOrFail($id);
        $event->update(['status' => 'archived']);

        return back()->with('success', 'Event telah diarsipkan.');
    }

    public function restore($id)
    {
        $event = Event::findOrFail($id);
        $event->update(['status' => 'active']);

        return back()->with('success', 'Event telah diaktifkan kembali.');
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $name = $event->nama_event;
        $event->delete();

        return redirect()->route('events.index')
            ->with('success', 'Event '.$name.' berhasil dihapus.');
    }

    public function addDivision(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'nama_divisi' => ['required', 'string', 'max:100'],
        ]);

        EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => $validated['nama_divisi'],
            'created_at' => now(),
        ]);

        return back()->with('success', 'Divisi '.$validated['nama_divisi'].' berhasil ditambahkan.');
    }

    public function updateDivision(Request $request, $id, $divisionId)
    {
        $division = EventDivision::where('event_id', $id)->findOrFail($divisionId);

        $validated = $request->validate([
            'nama_divisi' => ['required', 'string', 'max:100'],
        ]);

        $division->update([
            'nama_divisi' => $validated['nama_divisi'],
        ]);

        return back()->with('success', 'Nama divisi berhasil diperbarui.');
    }

    public function deleteDivision($id, $divisionId)
    {
        $division = EventDivision::where('event_id', $id)->findOrFail($divisionId);
        $division->delete();

        return back()->with('success', 'Divisi berhasil dihapus.');
    }

    public function addMembers(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'division_id' => ['required', 'exists:event_divisions,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
            'jabatan_panitia' => ['nullable', 'string', 'max:100'],
        ]);

        $divisionId = $request->input('division_id');
        $jabatan = $request->input('jabatan_panitia', 'Anggota') ?: 'Anggota';
        $userIds = $request->input('user_ids', []);

        $addedCount = 0;
        foreach ($userIds as $userId) {
            $exists = EventMember::where('event_id', $event->id)
                ->where('user_id', $userId)
                ->exists();

            if (! $exists) {
                EventMember::create([
                    'event_id' => $event->id,
                    'division_id' => $divisionId,
                    'user_id' => $userId,
                    'jabatan_panitia' => $jabatan,
                    'created_at' => now(),
                ]);
                $addedCount++;
            }
        }

        if ($addedCount === 0) {
            return back()->with('warning', 'Anggota yang dipilih sudah terdaftar dalam kepanitiaan event ini.');
        }

        return back()->with('success', "{$addedCount} panitia berhasil ditambahkan ke divisi.");
    }

    public function removeMember($id, $memberId)
    {
        $em = EventMember::where('event_id', $id)->findOrFail($memberId);
        $em->delete();

        return back()->with('success', 'Panitia berhasil dikeluarkan dari divisi.');
    }

    public function complete($id, GamificationService $gamificationService)
    {
        $event = Event::findOrFail($id);

        if ($event->status === 'completed') {
            return back()->with('info', 'Event ini sudah dalam status selesai.');
        }

        $awardedCount = $gamificationService->completeEvent($event);

        return back()->with('success', "Event '{$event->nama_event}' berhasil diselesaikan! Sebanyak {$awardedCount} panitia telah menerima lencana kepanitiaan dan bonus EXP.");
    }

    public function reopen($id, GamificationService $gamificationService)
    {
        $event = Event::findOrFail($id);

        if ($event->status !== 'completed') {
            return back()->with('info', 'Event ini tidak dalam status selesai.');
        }

        $revertedCount = $gamificationService->reopenEvent($event);

        return back()->with('success', "Event '{$event->nama_event}' berhasil dibuka kembali. Lencana dan bonus EXP untuk {$revertedCount} panitia telah ditarik kembali.");
    }
}
