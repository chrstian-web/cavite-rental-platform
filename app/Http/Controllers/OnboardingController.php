<?php

namespace App\Http\Controllers;

use App\Services\OnboardingChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    /** The welcome popup was shown (and either started or skipped). */
    public function welcome(Request $request): Response
    {
        $user = $request->user();

        if ($user->onboarding_welcomed_at === null) {
            $user->forceFill(['onboarding_welcomed_at' => now()])->save();
        }

        return response()->noContent();
    }

    /** The guided tour was finished or skipped. */
    public function tourCompleted(Request $request): Response
    {
        $request->user()->forceFill([
            'onboarding_welcomed_at' => $request->user()->onboarding_welcomed_at ?? now(),
            'onboarding_tour_completed_at' => now(),
        ])->save();

        return response()->noContent();
    }

    public function dismissChecklist(Request $request): Response
    {
        $request->user()->forceFill(['onboarding_checklist_dismissed_at' => now()])->save();

        return response()->noContent();
    }

    /**
     * The renter opened a step's tutorial ("Do this next" / "See how"), so that step is ticked.
     * Only the steps listed in OnboardingChecklist::TUTORIAL_STEPS can be ticked this way;
     * the phone number is always ticked from real data.
     */
    public function stepSeen(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->isTenant(), 403);

        $data = $request->validate([
            'key' => ['required', 'string', Rule::in(OnboardingChecklist::TUTORIAL_STEPS)],
        ]);

        $seen = $user->onboarding_steps_seen ?? [];

        if (! isset($seen[$data['key']])) {
            $seen[$data['key']] = now()->toIso8601String();
            $user->forceFill(['onboarding_steps_seen' => $seen])->save();
        }

        return response()->noContent();
    }

    /** "Take the tour" from the profile menu: bring the checklist back and start the tour. */
    public function restart(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['onboarding_checklist_dismissed_at' => null])->save();

        return redirect()->route('dashboard', ['tour' => 1]);
    }
}
