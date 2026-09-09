<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class EventUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer',
            'title' => 'string',
            'description' => 'string',
            'where' => 'nullable|string',
            'type' => 'string',
            'points' => 'integer',
            'link' => 'string',
            'checked' => 'boolean',
            'target_type' => 'string',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
        ];
    }
}
