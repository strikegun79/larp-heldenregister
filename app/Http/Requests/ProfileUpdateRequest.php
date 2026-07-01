<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:100'],
            'house_number' => ['nullable', 'string', 'max:10'],
            'zip' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'teamer_notifications'       => ['nullable', 'boolean'],
            'notify_new_user'            => ['nullable', 'boolean'],
            'notify_booking_received'    => ['nullable', 'boolean'],
            'notify_booking_approved'    => ['nullable', 'boolean'],
            'notify_booking_rejected'    => ['nullable', 'boolean'],
            'notify_booking_cancelled'   => ['nullable', 'boolean'],
            'notify_payment_confirmed'   => ['nullable', 'boolean'],
            'notify_waitlist_promoted'   => ['nullable', 'boolean'],
            'notify_event_cancelled'     => ['nullable', 'boolean'],
            'notify_event_reminder'      => ['nullable', 'boolean'],
            'notify_cancellation_report' => ['nullable', 'boolean'],
        ];
    }
}
