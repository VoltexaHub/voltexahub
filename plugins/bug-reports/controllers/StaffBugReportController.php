<?php

namespace Plugins\BugReports\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Plugins\BugReports\Models\BugReport;

class StaffBugReportController extends Controller
{
    public function index(Request $request)
    {
        $query = BugReport::with([
            'reporter:id,username,avatar_url',
            'assignee:id,username',
        ]);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->query('severity'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->has('search')) {
            $query->where('title', 'LIKE', '%' . $request->query('search') . '%');
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'data' => $reports->items(),
            'meta' => [
                'total' => $reports->total(),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $report = BugReport::with([
            'reporter:id,username,avatar_url',
            'assignee:id,username',
        ])->findOrFail($id);

        return response()->json(['data' => $report]);
    }

    public function update(Request $request, $id)
    {
        $report = BugReport::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved,closed,wont_fix',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'staff_notes' => 'nullable|string|max:5000',
        ]);

        $report->update($validated);

        return response()->json(['data' => $report->fresh()]);
    }

    public function destroy($id)
    {
        $report = BugReport::findOrFail($id);

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
