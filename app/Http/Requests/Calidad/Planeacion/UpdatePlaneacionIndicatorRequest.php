<?php

namespace App\Http\Requests\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use Illuminate\Validation\Rule;

class UpdatePlaneacionIndicatorRequest extends StorePlaneacionIndicatorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $indicator = $this->route('indicator');
        $ignoreId = $indicator instanceof QualityPlanningIndicator ? $indicator->id : null;

        $rules['code'] = [
            'required',
            'string',
            'max:40',
            Rule::unique((new QualityPlanningIndicator)->getTable(), 'code')->ignore($ignoreId),
        ];

        return $rules;
    }
}
