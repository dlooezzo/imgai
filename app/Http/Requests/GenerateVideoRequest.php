<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVideoRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'prompt' => 'required|string|min:3|max:2500',
            'aspect_ratio' => 'nullable|string|in:1:1,16:9,9:16,4:3,21:9',
            'width' => 'nullable|integer|min:256|max:1920',
            'height' => 'nullable|integer|min:256|max:1920',
            'steps' => 'nullable|integer|min:10|max:100',
            'crf' => 'nullable|integer|min:10|max:51',
            'flow_shift' => 'nullable|integer|min:1|max:20',
            'frame_rate' => 'nullable|integer|in:15,24,30,60',
            'guidance_scale' => 'nullable|numeric|min:1|max:20',
            'denoise_strength' => 'nullable|numeric|min:0.1|max:1.0',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'prompt.required' => 'Please provide a text prompt to generate video.',
            'prompt.min' => 'The video prompt must be at least 3 characters long.',
            'aspect_ratio.in' => 'Selected aspect ratio is not supported.',
        ];
    }
}
