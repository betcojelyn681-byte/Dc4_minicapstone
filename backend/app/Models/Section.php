<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'section_code', 'year_level', 'academic_term'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function students()
    {
        return $this->hasMany(User::class);
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
