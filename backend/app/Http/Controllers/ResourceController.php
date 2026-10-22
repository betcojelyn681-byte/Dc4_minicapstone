<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Room;

class ResourceController extends Controller
{
    private function resource(Request $request): string
    {
        $routeName = $request->route()->getName();
        return explode('.', $routeName)[0]; // e.g., "courses.index" -> "courses"
    }

    private function model(string $resource): string
    {
        return match ($resource) {
            'courses' => Course::class,
            'subjects' => Subject::class,
            'sections' => Section::class,
            'rooms' => Room::class,
            default => abort(404),
        };
    }

    private function rules(string $resource): array
    {
        return match ($resource) {
            'courses' => [
                'course_code' => 'required|string|max:30',
                'name' => 'required|string|max:180',
                'department' => 'required|string|max:120',
            ],
            'subjects' => [
                'course_id' => 'required|exists:courses,id',
                'subject_code' => 'required|string|max:30',
                'title' => 'required|string|max:180',
                'units' => 'required|integer|min:0|max:12',
            ],
            'sections' => [
                'course_id' => 'required|exists:courses,id',
                'section_code' => 'required|string|max:40',
                'year_level' => 'required|integer|min:1|max:8',
                'academic_term' => 'required|string|max:80',
            ],
            'rooms' => [
                'room_code' => 'required|string|max:30',
                'building' => 'required|string|max:100',
                'capacity' => 'required|integer|min:1',
                'room_type' => 'required|string|max:60',
                'is_available' => 'sometimes|boolean',
            ],
            default => abort(404),
        };
    }

    public function index(Request $request)
    {
        $resource = $this->resource($request);
        $model = $this->model($resource);
        $query = $model::query();

        if ($request->filled('search')) {
            $term = '%' . $request->query('search') . '%';
            $cols = match ($resource) {
                'courses' => ['course_code', 'name', 'department'],
                'subjects' => ['subject_code', 'title'],
                'sections' => ['section_code', 'academic_term'],
                'rooms' => ['room_code', 'building', 'room_type'],
            };
            $query->where(fn ($w) => collect($cols)->each(fn ($c) => $w->orWhere($c, 'like', $term)));
        }

        if (in_array($resource, ['subjects', 'sections'])) {
            $query->with('course');
        }

        return $query->orderBy('id')->paginate(min(max((int) $request->query('per_page', 15), 1), 100));
    }

    public function store(Request $request)
    {
        $resource = $this->resource($request);
        $rules = $this->rules($resource);
        $unique = match ($resource) {
            'courses' => ['course_code', 'courses'],
            'subjects' => ['subject_code', 'subjects'],
            'sections' => ['section_code', 'sections'],
            'rooms' => ['room_code', 'rooms'],
        };
        $rules[$unique[0]] .= '|unique:' . $unique[1] . ',' . $unique[0];

        return response()->json(($this->model($resource))::create($request->validate($rules)), 201);
    }

    public function show(Request $request, string $id)
    {
        $resource = $this->resource($request);
        $model = $this->model($resource);
        return $model::findOrFail((int) $id);
    }

    public function update(Request $request, string $id)
    {
        $resource = $this->resource($request);
        $model = $this->model($resource);
        $record = $model::findOrFail((int) $id);
        $rules = $this->rules($resource);
        $unique = match ($resource) {
            'courses' => ['course_code', 'courses'],
            'subjects' => ['subject_code', 'subjects'],
            'sections' => ['section_code', 'sections'],
            'rooms' => ['room_code', 'rooms'],
        };
        $rules[$unique[0]] .= '|unique:' . $unique[1] . ',' . $unique[0] . ',' . $id;
        $record->update($request->validate($rules));
        return $record->fresh();
    }

    public function destroy(Request $request, string $id)
    {
        $resource = $this->resource($request);
        $model = $this->model($resource);
        try {
            $model::findOrFail((int) $id)->delete();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'This record is in use. Remove related records first.'], 422);
        }
        return response()->json(['message' => 'Deleted.']);
    }
}
