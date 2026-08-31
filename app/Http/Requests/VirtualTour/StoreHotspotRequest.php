<?php

namespace App\Http\Requests\VirtualTour;

use Illuminate\Foundation\Http\FormRequest;

class StoreHotspotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        return [
            // Pannellum-style coordinates: yaw (-180 to 180) and pitch (-90 to 90)
            'position_x' => ['required', 'numeric', 'between:-180,180'], // yaw
            'position_y' => ['required', 'numeric', 'between:-90,90'],  // pitch
            'target_scene_id' => [
                'nullable',
                'exists:virtual_tour_scenes,id',
                // A hotspot may only link to a scene within the SAME tour (and
                // therefore the same property) it belongs to — never across properties.
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $targetScene = \App\Models\VirtualTourScene::find($value);
                    $currentScene = \App\Models\VirtualTourScene::find($this->route('scene')?->id);

                    if ($targetScene && $currentScene && $targetScene->virtual_tour_id !== $currentScene->virtual_tour_id) {
                        $fail('The destination scene must belong to the same property tour.');
                    }
                },
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:navigation,info'],
        ];
    }
}
