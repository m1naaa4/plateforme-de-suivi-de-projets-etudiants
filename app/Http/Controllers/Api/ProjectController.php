<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return Project::with(['teacher', 'group', 'tasks', 'deliverables'])->get();
    }

    public function store(Request $request)
{
    $user = $request->user();

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'teacher_id' => 'nullable|exists:users,id',
        'status' => 'nullable|in:en_attente,en_cours,termine',
        'start_date' => 'nullable|date',
        'deadline' => 'nullable|date|after_or_equal:start_date',
        'progress' => 'nullable|integer|min:0|max:100',
    ]);

    if ($user && $user->role === 'enseignant') {
        $validated['teacher_id'] = $user->id;
    }

    $validated['progress'] = $validated['progress'] ?? 0;

    $project = Project::create($validated);

    return response()->json($project->load(['teacher', 'group', 'tasks', 'deliverables']), 201);
}


   public function show(string $id)
{
    return Project::with(['teacher', 'group', 'tasks', 'deliverables'])->findOrFail($id);
}

   public function update(Request $request, string $id)
{
    $project = Project::findOrFail($id);

    $validated = $request->validate([
        'title' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'teacher_id' => 'nullable|exists:users,id',
        'status' => 'nullable|in:en_attente,en_cours,termine',
        'start_date' => 'nullable|date',
        'deadline' => 'nullable|date|after_or_equal:start_date',
        'progress' => 'nullable|integer|min:0|max:100',
    ]);

    $project->update($validated);

    return response()->json($project->fresh(['teacher', 'group', 'tasks', 'deliverables']));
}


    public function destroy(string $id)
{
    $project = Project::findOrFail($id);

    $project->delete();

    return response()->json([
        'message' => 'Projet supprimé avec succès'
    ]);
}
}
