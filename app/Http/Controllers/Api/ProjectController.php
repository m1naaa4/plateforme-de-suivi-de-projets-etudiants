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
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'teacher_id' => 'nullable|exists:users,id',
        'status' => 'nullable|in:en_attente,en_cours,termine',
    ]);

    $project = Project::create($validated);

    return response()->json($project, 201);
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
    ]);

    $project->update($validated);

    return response()->json($project);
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