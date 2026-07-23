<?php

namespace App\Http\Requests\Manifestation;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class RestoreManifestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->isActive()) {
            return false;
        }

        return in_array($user->role, [
            UserRole::Administrator,
            UserRole::Manager,
        ], true);
    }

    public function rules(): array
    {
        return [];
    }
}
