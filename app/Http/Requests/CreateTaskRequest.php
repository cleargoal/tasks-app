<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\TaskCreateData;
use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return TaskCreateData::rules();
    }

    public function toData(): TaskCreateData
    {
        // Map snake_case request fields to camelCase data fields
        // Ensure required fields have default values
        $data = [
            'title' => $this->input('title', ''),
            'description' => $this->input('description', ''),
            'status' => $this->input('status'),
            'priority' => $this->input('priority'),
            'parentId' => $this->input('parent_id'),
            'dueDate' => $this->input('due_date'),
        ];

        // Filter out null values for optional fields
        $data = array_filter($data, function ($value, $key) {
            // Keep required fields even if null
            if ($key === 'title') {
                return true;
            }
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        return TaskCreateData::from($data);
    }
}
