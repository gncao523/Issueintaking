<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'author_name' => is_string($this->author_name) ? trim($this->author_name) : $this->author_name,
            'body' => is_string($this->body) ? trim($this->body) : $this->body,
        ]);
    }

    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'min:1', 'max:255'],
            'body' => ['required', 'string', 'min:1'],
        ];
    }
}
