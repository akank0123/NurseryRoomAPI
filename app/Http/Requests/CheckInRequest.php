<?php

namespace App\Http\Requests;

use App\Models\Child;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'child_id' => ['required', 'string', 'regex:/^[a-f0-9]{24}$/i'],
        ];
    }

    /**
     * Look the child up through the tenant-scoped model, so a child from
     * another tenant is reported exactly like one that does not exist.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isEmpty() && $this->child() === null) {
                    $validator->errors()->add('child_id', 'The selected child does not exist.');
                }
            },
        ];
    }

    public function child(): ?Child
    {
        return once(fn () => Child::find($this->string('child_id')->value()));
    }
}
