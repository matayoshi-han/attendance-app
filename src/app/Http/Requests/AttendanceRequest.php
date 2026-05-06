<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_out' => 'after:clock_in',
            'clock_in' => 'before:clock_out',
            'rests.*.start' => 'nullable|after:clock_in|before:clock_out',
            'rests.*.end' => 'nullable|before:clock_out',
            'remarks'       => 'required',
        ];
    }

    public function messages()
    {
        return [
            'clock_out.after'    => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in.before'    => '出勤時間もしくは退勤時間が不適切な値です',
            'rests.*.start.before' => '休憩時間が不適切な値です',
            'rests.*.start.after' => '休憩時間が不適切な値です',
            'rests.*.end.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'remarks.required' => '備考を記入してください',
        ];
    }
}
