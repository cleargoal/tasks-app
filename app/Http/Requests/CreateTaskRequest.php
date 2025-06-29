<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\TaskCreateData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest for creating a new task.
 *
 * This class handles validation and data transformation for task creation requests.
 * It converts the incoming request data into a TaskCreateData object that can be
 * used by the service layer.
 */
class CreateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * For task creation, all authenticated users are authorized.
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
     * Uses the rules defined in TaskCreateData.
     *
     * @return array The validation rules
     */
    public function rules(): array
    {
        return TaskCreateData::rules();
    }

    /**
     * Transform the request data into a TaskCreateData object.
     *
     * Filters out null values except for the title field which is required.
     *
     * @return TaskCreateData The data object for task creation
     */
    public function toData(): TaskCreateData
    {
        $data = [
            'title' => $this->input('title', ''),
            'description' => $this->input('description', ''),
            'status' => $this->input('status'),
            'priority' => $this->input('priority'),
            'parentId' => $this->input('parent_id'),
            'dueDate' => $this->input('due_date'),
        ];

        $data = array_filter($data, function ($value, $key) {
            if ($key === 'title') {
                return true;
            }
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        return TaskCreateData::from($data);
    }
}
