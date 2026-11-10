<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityArchiveController extends Controller
{
    public function __construct(protected AdminActivityService $activities)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.activity.archive', [
            'archivedActivity' => $this->activities->recent($request->user(), 100, true),
        ]);
    }

    public function archive(Request $request, string $type, int $activityId): RedirectResponse
    {
        abort_unless($this->activities->isSupportedType($type), 404);

        $this->activities->archive($request->user(), $type, $activityId);

        return back()->with('status', 'Activity moved to the archive.');
    }

    public function restore(Request $request, string $type, int $activityId): RedirectResponse
    {
        abort_unless($this->activities->isSupportedType($type), 404);

        $this->activities->restore($request->user(), $type, $activityId);

        return back()->with('status', 'Activity restored to Recent Activity.');
    }
}
