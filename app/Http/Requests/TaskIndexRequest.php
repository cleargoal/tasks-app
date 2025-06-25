<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['nullable', 'string'],
            'filters.priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'filters.status' => ['nullable', 'string', 'in:todo,done'],
            'filters.title' => ['nullable', 'string', 'max:255'],
            'filters.description' => ['nullable', 'string'],
            'filters.dueDate' => ['nullable', 'date_format:Y-m-d'],
            'filters.completedAt' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'filters.dueDate.date_format' => 'The due date filter must be in YYYY-MM-DD format.',
            'filters.completedAt.date_format' => 'The completed date filter must be in YYYY-MM-DD format.',
            'filters.priority.min' => 'Priority filter must be between 1 and 5.',
            'filters.priority.max' => 'Priority filter must be between 1 and 5.',
            'filters.status.in' => 'Status filter must be either "todo" or "done".',
        ];
    }
}
