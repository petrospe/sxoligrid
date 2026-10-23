<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingMethod;
use App\Enums\CourseType;
use App\Http\Controllers\Controller;
use App\Models\BillingPolicy;
use App\Models\Course;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Courses/Index', [
            'courses' => Course::query()->orderBy('title')->get()->map(fn (Course $course): array => $this->present($course))->all(),
            'types' => CourseType::values(),
            'billing_methods' => BillingMethod::values(),
            'discounts' => BillingPolicy::current()->tiers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Course::query()->create($this->validated($request));

        return redirect()->route('admin.courses.index')->with('success', __('ui.flash.course_created'));
    }

    public function edit(Course $course): Response
    {
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $this->present($course),
            'types' => CourseType::values(),
            'billing_methods' => BillingMethod::values(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validated($request));

        return redirect()->route('admin.courses.index')->with('success', __('ui.flash.course_updated'));
    }

    public function destroy(Course $course): RedirectResponse
    {
        try {
            $course->delete();
        } catch (QueryException) {
            return back()->with('error', __('ui.flash.course_delete_blocked'));
        }

        return redirect()->route('admin.courses.index')->with('success', __('ui.flash.course_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $methods = array_values(array_filter(
            BillingMethod::values(),
            fn (string $method): bool => in_array($method, (array) $request->input('billing_methods', []), true),
        ));
        $incoming = $request->input('prices', []);
        $prices = [];

        if (is_array($incoming)) {
            foreach ($methods as $method) {
                if (array_key_exists($method, $incoming)) {
                    $prices[$method] = $incoming[$method];
                }
            }
        }

        $request->merge([
            'billing_methods' => $methods,
            'prices' => $prices,
        ]);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(CourseType::values())],
            'billing_methods' => ['required', 'array', 'min:1'],
            'billing_methods.*' => ['distinct', Rule::in(BillingMethod::values())],
            'prices' => ['required', 'array'],
            'prices.*' => ['required', 'numeric', 'min:0'],
        ], [], [
            'billing_methods' => __('ui.fields.billing'),
            'prices' => __('ui.fields.prices'),
            'prices.*' => __('ui.fields.price'),
        ]);

        $validator->after(function ($validator) use ($methods, $prices): void {
            if ($methods === []) {
                return;
            }

            foreach ($methods as $method) {
                if (! array_key_exists($method, $prices) || $prices[$method] === '' || $prices[$method] === null) {
                    $validator->errors()->add('prices.'.$method, __('validation.required', [
                        'attribute' => __('ui.billing_methods.'.$method),
                    ]));
                }
            }
        });

        $data = $validator->validate();
        $normalized = [];

        foreach ($data['billing_methods'] as $method) {
            $normalized[$method] = round((float) $data['prices'][$method], 2);
        }

        $data['prices'] = $normalized;
        $data['price'] = $normalized['monthly'] ?? $normalized[$data['billing_methods'][0]];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'price' => (float) $course->price,
            'prices' => $course->pricesForMethods(),
            'type' => $course->type->value,
            'billing_methods' => array_values($course->billing_methods ?? ['monthly']),
        ];
    }
}
