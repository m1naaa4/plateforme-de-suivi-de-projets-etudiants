<?php

namespace App\Http\Controllers\Api;
use App\Models\Group;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;


class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    return Group::with(['project', 'users'])->get();
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'project_id' => 'nullable|exists:projects,id',
        'users' => 'nullable|array',
        'users.*' => 'exists:users,id',
    ]);

    $group = Group::create([
        'name' => $validated['name'],
        'project_id' => $validated['project_id'] ?? null,
    ]);

    if (isset($validated['users'])) {
        $group->users()->sync($validated['users']);
    }

    return response()->json($group->load(['project', 'users']), 201);
}


    /**
     * Display the specified resource.
     */
   public function show(string $id)
{
    return Group::with(['project', 'users'])->findOrFail($id);
}


    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, string $id)
{
    $group = Group::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'project_id' => 'nullable|exists:projects,id',
        'users' => 'nullable|array',
        'users.*' => 'exists:users,id',
    ]);

    $group->update([
        'name' => $validated['name'] ?? $group->name,
        'project_id' => array_key_exists('project_id', $validated)
            ? $validated['project_id']
            : $group->project_id,
    ]);

    if (isset($validated['users'])) {
        $group->users()->sync($validated['users']);
    }

    return response()->json($group->load(['project', 'users']));
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
{
    $group = Group::findOrFail($id);

    $group->delete();

    return response()->json([
        'message' => 'Groupe supprimé avec succès'
    ]);
}

}
