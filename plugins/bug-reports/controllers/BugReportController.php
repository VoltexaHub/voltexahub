<?php

namespace Plugins\BugReports\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Plugins\BugReports\Models\BugReport;

class BugReportController extends Controller
{
    public function index(Request $request)
    {
        $query = BugReport::where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'data' => $reports->map(function ($report) {
                return collect($report->toArray())->except('staff_notes')->all();
            }),
            'meta' => [
                'total' => $reports->total(),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:150',
            'description' => 'required|max:5000',
            'steps_to_reproduce' => 'nullable|max:3000',
            'severity' => 'nullable|in:low,medium,high,critical',
            'environment' => 'nullable|max:255',
            'url' => 'nullable|max:500',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|image|max:5120',
        ]);

        $report = BugReport::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'steps_to_reproduce' => $validated['steps_to_reproduce'] ?? null,
            'severity' => $validated['severity'] ?? 'medium',
            'environment' => $validated['environment'] ?? null,
            'url' => $validated['url'] ?? null,
        ]);

        if ($request->hasFile('attachments')) {
            $paths = [];
            foreach ($request->file('attachments') as $file) {
                $storedPath = $file->store("bug-reports/{$report->id}", 'public');
                $paths[] = '/storage/' . $storedPath;
            }
            $report->update(['attachments' => $paths]);
        }

        return response()->json(['data' => $report->fresh()], 201);
    }

    public function show(Request $request, $id)
    {
        $report = BugReport::findOrFail($id);

        $user = $request->user();
        $isOwner = $report->user_id === $user->id;
        $isStaff = $user->hasRole('admin') || (method_exists($user, 'roles') && $user->roles()->where('is_staff', true)->exists());

        if (!$isOwner && !$isStaff) {
            abort(403, 'Unauthorized.');
        }

        $data = $report->toArray();
        if (!$isStaff) {
            unset($data['staff_notes']);
        }

        return response()->json(['data' => $data]);
    }

    public function destroy(Request $request, $id)
    {
        $report = BugReport::findOrFail($id);

        if ($report->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        if ($report->status !== 'open') {
            abort(403, 'Only open bug reports can be deleted.');
        }

        if (!empty($report->attachments)) {
            foreach ($report->attachments as $path) {
                $storagePath = str_replace('/storage/', '', $path);
                Storage::disk('public')->delete($storagePath);
            }
            Storage::disk('public')->deleteDirectory("bug-reports/{$report->id}");
        }

        $report->delete();

        return response()->noContent();
    }
}
