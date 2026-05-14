<?php

namespace App\Http\Controllers\Api;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    return Task::with(['project', 'assignedUser', 'deliverables'])->get();
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'project_id' => 'required|exists:projects,id',
        'assigned_to' => 'nullable|exists:users,id',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'nullable|in:a_faire,en_cours,termine',
        'deadline' => 'nullable|date',
    ]);

    $task = Task::create($validated);

    return response()->json($task, 201);
}


    /**
     * Display the specified resource.
     */
   public function show(string $id)
{
    return Task::with(['project', 'assignedUser', 'deliverables'])->findOrFail($id);
}


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
{
    $task = Task::findOrFail($id);

    $validated = $request->validate([
        'project_id' => 'sometimes|required|exists:projects,id',
        'assigned_to' => 'nullable|exists:users,id',
        'title' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'nullable|in:a_faire,en_cours,termine',
        'deadline' => 'nullable|date',
    ]);

    $task->update($validated);

    return response()->json($task);
}


    /**
     * Remove the specified resource from storage.
     */
   public function destroy(string $id)
{
    $task = Task::findOrFail($id);

    $task->delete();

    return response()->json([
        'message' => 'Tâche supprimée avec succès'
    ]);
}

}
