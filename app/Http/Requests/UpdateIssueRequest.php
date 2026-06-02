<?php

namespace App\Http\Requests;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title') && is_string($this->title)) {
            $this->merge(['title' => trim($this->title)]);
        }
        if ($this->has('description') && is_string($this->description)) {
            $this->merge(['description' => trim($this->description)]);
        }
        if ($this->has('category') && is_string($this->category)) {
            $this->merge(['category' => trim($this->category)]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:1', 'max:255'],
            'description' => ['sometimes', 'string', 'min:1'],
            'priority' => ['sometimes', Rule::in(IssuePriority::values())],
            'category' => ['sometimes', 'string', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::in(IssueStatus::values())],
        ];
    }
}
