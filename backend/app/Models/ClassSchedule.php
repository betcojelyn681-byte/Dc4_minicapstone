<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['subject_id', 'custom_name', 'section_id', 'room_id', 'instructor_name', 'day', 'start_time', 'end_time', 'status'];

    protected $appends = ['display_name', 'display_code'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->custom_name ?? $this->subject?->title ?? 'Unnamed Class';
    }

    public function getDisplayCodeAttribute(): string
    {
        return $this->subject?->subject_code ?? 'CUSTOM';
    }
}
