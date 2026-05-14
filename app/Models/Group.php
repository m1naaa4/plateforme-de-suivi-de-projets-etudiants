<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
   protected $fillable = [
    'name',
    'project_id',
];
public function project()
{
    return $this->belongsTo(Project::class);
}

public function users()
{
    return $this->belongsToMany(User::class);
}

}
