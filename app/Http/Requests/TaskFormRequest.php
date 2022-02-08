<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Support\Arr;
use App\Services\BaseTranslate;
use Illuminate\Validation\Rule;
use App\Services\TranslateManager;
use Illuminate\Foundation\Http\FormRequest;

class TaskFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->services) {
            $this->merge([
                'services' => Arr::wrap($this->services),
            ]);
        }
        if ($this->source) {
            $this->merge([
                'source' => Arr::only($this->source, Task::JSON_FIELDS),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'text' => 'required|string',
            'source' => 'array',
            'source.root' => 'string',
            'source.card' => 'string',
            'source.service' => 'string',
            'from' => ['filled', 'string', Rule::in(array_keys(BaseTranslate::LANGUAGES))],
            'to' => ['filled', 'string', 'different:from', Rule::in(array_keys(BaseTranslate::LANGUAGES))],
            'revert' => 'boolean',
            'services' => ['array', Rule::in(TranslateManager::SERVICES)],
        ];
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation()
    {
        $data = $this->validator->attributes();
        Arr::set($data, 'source', json_encode($data['source']));
        $this->validator->setData($data);
    }
}
