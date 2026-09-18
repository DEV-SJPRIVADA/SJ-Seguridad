<?php

namespace App\Http\Requests\DevelopmentRequests;

use App\Models\DevelopmentRequest;

class UpdateDevelopmentRequestRequest extends StoreDevelopmentRequestRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var DevelopmentRequest|null $developmentRequest */
        $developmentRequest = $this->route('development_request');

        return $user !== null
            && $developmentRequest instanceof DevelopmentRequest
            && $user->can('update', $developmentRequest);
    }
}
