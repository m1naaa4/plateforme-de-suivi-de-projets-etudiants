<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
   protected $fillable = [
    'title',
    'description',
    'teacher_id',
    'status',
];
public function teacher()
{
    return $this->belongsTo(User::class, 'teacher_id');
}

public function tasks()
{
    return $this->hasMany(Task::class);
}

public function group()
{
    return $this->hasOne(Group::class);
}

public function deliverables()
{
    return $this->hasMany(Deliverable::class);
}

}
