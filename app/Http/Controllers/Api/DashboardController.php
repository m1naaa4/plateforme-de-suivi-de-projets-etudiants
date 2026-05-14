<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'projects_count' => Project::count(),
            'tasks_count' => Task::count(),
            'completed_tasks_count' => Task::where('status', 'termine')->count(),
            'deliverables_count' => Deliverable::count(),
            'pending_deliverables_count' => Deliverable::where('status', 'en_attente')->count(),
        ]);
    }
}
