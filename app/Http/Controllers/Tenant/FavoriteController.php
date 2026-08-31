<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $favorites = $request->user()
            ->favorites()
            ->with(['property.images' => fn ($q) => $q->where('is_cover', true), 'property.location'])
            ->latest()
            ->paginate(12);

        return view('tenant.favorites.index', compact('favorites'));
    }

    /**
     * Toggle: add if not favorited, remove if already favorited.
     * firstOrCreate + the unique(user_id, property_id) DB constraint from
     * Step 1 both guard against duplicate favorite rows.
     */
    public function toggle(Request $request, Property $property): RedirectResponse
    {
        $favorite = Favorite::where('user_id', $request->user()->id)
            ->where('property_id', $property->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $message = 'Removed from favorites.';
        } else {
            Favorite::create(['user_id' => $request->user()->id, 'property_id' => $property->id]);
            $message = 'Added to favorites.';
        }

        return back()->with('status', $message);
    }
}
