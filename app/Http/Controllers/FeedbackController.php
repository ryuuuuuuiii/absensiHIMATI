<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $kategori = $request->query('kategori');
        $search = $request->query('search');

        $query = Feedback::with('member')->latest();

        if ($status && in_array($status, ['pending', 'read', 'resolved'])) {
            $query->where('status', $status);
        }

        if ($kategori && in_array($kategori, ['bug', 'saran', 'pertanyaan', 'lainnya'])) {
            $query->where('kategori', $kategori);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subjek', 'like', "%{$search}%")
                    ->orWhere('pesan', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($mq) use ($search) {
                        $mq->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('departemen', 'like', "%{$search}%");
                    });
            });
        }

        $feedbacks = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Feedback::count(),
            'pending' => Feedback::where('status', 'pending')->count(),
            'read' => Feedback::where('status', 'read')->count(),
            'resolved' => Feedback::where('status', 'resolved')->count(),
        ];

        return view('feedbacks.index', compact('feedbacks', 'stats', 'status', 'kategori', 'search'));
    }

    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,read,resolved'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $feedback->update($validated);

        return back()->with('success', 'Status feedback berhasil diperbarui.');
    }

    public function destroy(Feedback $feedback)
    {
        if ($feedback->screenshot) {
            $filepath = public_path('uploads/feedbacks/'.$feedback->screenshot);
            if (File::exists($filepath)) {
                File::delete($filepath);
            }
        }

        $feedback->delete();

        return back()->with('success', 'Data feedback berhasil dihapus.');
    }
}
