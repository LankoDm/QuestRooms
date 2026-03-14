<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
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
            'name' => 'required|min:10|max:255|unique:rooms,name',
            'description' => 'required|min:20',
            'difficulty' => 'required|in:easy,medium,hard,ultra hard',
            'image_path' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            'min_players' => 'required|integer|min:1',
            'max_players' => 'required|integer|gte:min_players',
            'weekday_price' => 'required|integer|min:0',
            'weekend_price' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:10',
            'is_active' => 'required|boolean',
            'slug' => 'required|unique:rooms,slug',
        ];
    }

//    public function messages(): array //Дописать потом
//    {
//        return [
//
//        ];
//    }
}
