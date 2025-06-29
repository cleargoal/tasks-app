<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\TaskUpdateData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return TaskUpdateData::rules();
    }

    public function toData(): TaskUpdateData
    {
        $data = [
            'title' => $this->input('title'),
            'description' => $this->input('description'),
            'status' => $this->input('status'),
            'priority' => $this->input('priority'),
            'parentId' => $this->input('parent_id'),
            'dueDate' => $this->input('due_date'),
            'completedAt' => $this->input('completed_at'),
        ];

        return TaskUpdateData::from($data);
    }
}
