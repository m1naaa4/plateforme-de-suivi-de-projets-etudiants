<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deliverable;
use Illuminate\Http\Request;

class DeliverableController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Deliverable::with(['project', 'task', 'submitter']);

        if ($user->role === 'admin') {
            return $query->latest()->get();
        }

        if ($user->role === 'enseignant') {
            return $query->latest()->get();
        }

        if ($user->role === 'etudiant') {
            return $query
                ->where('submitted_by', $user->id)
                ->latest()
                ->get();
        }

        return collect([]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('deliverables', 'public');

        $deliverable = Deliverable::create([
            'project_id' => $validated['project_id'],
            'task_id' => $validated['task_id'] ?? null,
            'submitted_by' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'en_attente',
            'teacher_comment' => null,
        ]);

        return response()->json(
            $deliverable->load(['project', 'task', 'submitter']),
            201
        );
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $deliverable = Deliverable::with(['project', 'task', 'submitter'])->findOrFail($id);

        if ($user->role === 'etudiant' && $deliverable->submitted_by !== $user->id) {
            abort(403);
        }

        return $deliverable;
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'enseignant'])) {
            abort(403);
        }

        $deliverable = Deliverable::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:en_attente,valide,refuse',
            'teacher_comment' => 'nullable|string',
        ]);

        $deliverable->update($validated);

        return response()->json(
            $deliverable->load(['project', 'task', 'submitter'])
        );
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $deliverable = Deliverable::findOrFail($id);

        if ($user->role === 'etudiant' && $deliverable->submitted_by !== $user->id) {
            abort(403);
        }

        $deliverable->delete();

        return response()->json([
            'message' => 'Livrable supprime avec succes',
        ]);
    }
}
