<?php

namespace Database\Seeders;
use App\Models\Project;
use App\Models\Group;
use App\Models\Task;
use App\Models\Deliverable;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
       User::firstOrCreate(
    ['email' => 'admin@test.com'],
    [
        'name' => 'Admin Principal',
        'password' => Hash::make('password'),
        'role' => 'admin',
    ]
);

User::firstOrCreate(
    ['email' => 'enseignant@test.com'],
    [
        'name' => 'Enseignant Test',
        'password' => Hash::make('password'),
        'role' => 'enseignant',
    ]
);

User::firstOrCreate(
    ['email' => 'etudiant@test.com'],
    [
        'name' => 'Etudiant Test',
        'password' => Hash::make('password'),
        'role' => 'etudiant',
    ]
);
$teacher = User::where('email', 'enseignant@test.com')->first();
$student = User::where('email', 'etudiant@test.com')->first();

$project = Project::firstOrCreate(
    ['title' => 'Plateforme de suivi des projets étudiants'],
    [
        'description' => 'Application Laravel et Vue.js pour suivre les projets PFA.',
        'teacher_id' => $teacher->id,
        'status' => 'en_cours',
        'start_date' => now()->toDateString(),
        'deadline' => now()->addMonth()->toDateString(),
        'progress' => 35,
    ]
);

$group = Group::firstOrCreate(
    ['name' => 'Groupe PFA 1'],
    [
        'project_id' => $project->id,
    ]
);

$group->users()->syncWithoutDetaching([$student->id]);

$task = Task::firstOrCreate(
    ['title' => 'Préparer le diagramme UML'],
    [
        'project_id' => $project->id,
        'assigned_to' => $student->id,
        'description' => 'Préparer les diagrammes de cas d’utilisation et de classes.',
        'status' => 'en_cours',
        'deadline' => now()->addDays(7),
    ]
);

Deliverable::firstOrCreate(
    ['file_name' => 'diagramme_uml.pdf'],
    [
        'project_id' => $project->id,
        'task_id' => $task->id,
        'submitted_by' => $student->id,
        'file_path' => 'deliverables/diagramme_uml.pdf',
        'status' => 'en_attente',
        'teacher_comment' => null,
    ]
);

}
}
