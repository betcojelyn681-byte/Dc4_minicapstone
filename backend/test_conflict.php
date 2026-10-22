<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$schedules = \App\Models\ClassSchedule::where('room_id', 1)->where('day', 'Monday')->get(['id', 'start_time', 'end_time']);
foreach($schedules as $s) {
    echo $s->id . ': ' . $s->start_time . ' - ' . $s->end_time . PHP_EOL;
}

// Test the conflict query
$day = 'Monday';
$start_time = '09:30';
$end_time = '11:00';
$room_id = 1;

$conflicts = \App\Models\ClassSchedule::where('day', $day)
    ->where('start_time', '<', $end_time)
    ->where('end_time', '>', $start_time)
    ->where('room_id', $room_id)
    ->get(['id', 'start_time', 'end_time']);

echo "Conflicts found: " . $conflicts->count() . PHP_EOL;
foreach($conflicts as $c) {
    echo "  Conflict: " . $c->id . ': ' . $c->start_time . ' - ' . $c->end_time . PHP_EOL;
}