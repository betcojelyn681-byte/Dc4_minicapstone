<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Delete schedule 7
\App\Models\ClassSchedule::where('id', 7)->delete();
echo "Deleted schedule 7" . PHP_EOL;

// Now test again
$d = [
    'day' => 'Monday',
    'start_time' => '09:30',
    'end_time' => '11:00',
    'room_id' => 1,
    'section_id' => 2,
    'instructor_name' => 'Another Teacher',
];

$q = \App\Models\ClassSchedule::where('day', $d['day'])
    ->where('start_time', '<', $d['end_time'])
    ->where('end_time', '>', $d['start_time'])
    ->where(fn($w) => $w->where('room_id', $d['room_id'])
        ->orWhere('section_id', $d['section_id'])
        ->orWhere('instructor_name', $d['instructor_name']));

$conflicts = $q->get(['id', 'start_time', 'end_time', 'room_id', 'section_id', 'instructor_name']);
echo "Conflicts found: " . $conflicts->count() . PHP_EOL;
foreach($conflicts as $c) {
    echo "  Conflict: " . $c->id . ': ' . $c->start_time . ' - ' . $c->end_time . ' (room:' . $c->room_id . ', section:' . $c->section_id . ', instructor:' . $c->instructor_name . ')' . PHP_EOL;
}