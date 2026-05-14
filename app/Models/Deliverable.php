<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deliverable extends Model
{
    protected $fillable = [
    'project_id',
    'task_id',
    'submitted_by',
    'file_name',
    'file_path',
    'status',
    'teacher_comment',
];
public function project()
{
    return $this->belongsTo(Project::class);
}

public function task()
{
    return $this->belongsTo(Task::class);
}

public function submitter()
{
    return $this->belongsTo(User::class, 'submitted_by');
}

}
