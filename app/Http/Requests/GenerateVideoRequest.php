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
            'prompt'         => 'required|string|min:3|max:2500',
            'resolution'     => 'nullable|string|in:480p,720p,1080p',
            'ratio'          => 'nullable|string|in:21:9,16:9,4:3,1:1,3:4,9:16,9:21,adaptive',
            'aspect_ratio'   => 'nullable|string|in:21:9,16:9,4:3,1:1,3:4,9:16,9:21,adaptive',
            'duration'       => 'nullable|integer|in:4,5,6,7,8,9,10,11,12',
            'generate_audio' => 'nullable|boolean',
            'seed'           => 'nullable|integer|min:0|max:2147483647',
            'camerafixed'    => 'nullable|boolean',
            'watermark'      => 'nullable|boolean',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'prompt.required'     => 'Please provide a text prompt to generate video.',
            'prompt.min'          => 'The video prompt must be at least 3 characters long.',
            'resolution.in'       => 'Supported resolutions are 480p, 720p, or 1080p.',
            'ratio.in'            => 'Selected aspect ratio is not supported.',
            'aspect_ratio.in'     => 'Selected aspect ratio is not supported.',
            'duration.in'         => 'Duration must be between 4 and 12 seconds.',
        ];
    }
}
