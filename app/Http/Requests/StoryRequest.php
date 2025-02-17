<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|unique:stories,title',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'cover' => 'required|url',
            'images' => 'required|array',
            'images.*' => 'url'
        ];
    }
}
