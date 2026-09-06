<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:2', 'max:2000'],
            'aspect_ratio' => ['nullable', 'string', Rule::in(['1:1', '16:9', '9:16', '4:3', '3:4', '21:9'])],
            'megapixels' => ['nullable', 'integer', Rule::in([1, 2])],
            'output_format' => ['nullable', 'string', Rule::in(['png', 'jpg', 'webp'])],
            'output_quality' => ['nullable', 'integer', 'min:1', 'max:100'],
            'seed' => ['nullable', 'numeric'],
            'juiced' => ['nullable', 'boolean'],
            'version' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prompt.required' => 'Please enter a description for the image you want to generate.',
            'prompt.min' => 'The prompt must be at least 2 characters long.',
            'prompt.max' => 'The prompt may not exceed 2000 characters.',
            'aspect_ratio.in' => 'Please select a valid aspect ratio (1:1, 16:9, 9:16, 4:3, 3:4, 21:9).',
            'output_format.in' => 'Output format must be PNG, JPG, or WEBP.',
            'output_quality.between' => 'Output quality must be between 1 and 100.',
        ];
    }
}
