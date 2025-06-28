<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\TaskFiltersData;
use App\Data\TaskSortingData;
use Illuminate\Foundation\Http\FormRequest;

class TaskFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    public function toFiltersData(): TaskFiltersData
    {
        return TaskFiltersData::from($this->input('filters', []));
    }

    public function toSortingData(): TaskSortingData
    {
        return TaskSortingData::fromString($this->input('sort'));
    }
}
