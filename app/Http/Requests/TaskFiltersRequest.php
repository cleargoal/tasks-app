<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\TaskFiltersData;
use App\Data\TaskSortingData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for filtering and sorting tasks.
 *
 * This class handles validation and data transformation for task filtering and sorting requests.
 * It converts the incoming request data into TaskFiltersData and TaskSortingData objects
 * that can be used by the service layer.
 */
class TaskFiltersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * For task filtering and sorting, all authenticated users are authorized.
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
     * Combines rules from TaskFiltersData and TaskSortingData, prefixing filter rules
     * with 'filters.' to match the request structure.
     *
     * @return array The validation rules
     */
    public function rules(): array
    {
        $filterRules = [];
        foreach (TaskFiltersData::rules() as $field => $rules) {
            $filterRules["filters.{$field}"] = $rules;
        }

        return array_merge(
            $filterRules,
            TaskSortingData::rules()
        );
    }

    /**
     * Get the custom validation error messages.
     *
     * Transforms messages from TaskFiltersData to match the prefixed field names
     * in the request structure.
     *
     * @return array The custom validation error messages
     */
    public function messages(): array
    {
        $messages = [];
        foreach (TaskFiltersData::messages() as $key => $message) {
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $field = $parts[0];
                $rule = $parts[1];
                $messages["filters.{$field}.{$rule}"] = $message;
            }
        }

        return $messages;
    }

    /**
     * Transform the request data into a TaskFiltersData object.
     *
     * Extracts the 'filters' input from the request and creates a TaskFiltersData object.
     *
     * @return TaskFiltersData The data object for task filtering
     */
    public function toFiltersData(): TaskFiltersData
    {
        return TaskFiltersData::from($this->input('filters', []));
    }

    /**
     * Transform the request data into a TaskSortingData object.
     *
     * Extracts the 'sort' input from the request and creates a TaskSortingData object.
     *
     * @return TaskSortingData The data object for task sorting
     */
    public function toSortingData(): TaskSortingData
    {
        return TaskSortingData::fromString($this->input('sort'));
    }
}
