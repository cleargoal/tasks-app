<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\TaskUpdateData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest for updating an existing task.
 *
 * This class handles validation and data transformation for task update requests.
 * It converts the incoming request data into a TaskUpdateData object that can be
 * used by the service layer.
 */
class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * For task updates, all authenticated users are authorized.
     *
     * @return bool Always returns true as authentication is handled by middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Uses the rules defined in TaskUpdateData.
     *
     * @return array The validation rules
     */
    public function rules(): array
    {
        return TaskUpdateData::rules();
    }

    /**
     * Transform the request data into a TaskUpdateData object.
     *
     * Unlike task creation, null values are allowed for updates to
     * represent fields that should not be changed.
     *
     * @return TaskUpdateData The data object for task update
     */
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
