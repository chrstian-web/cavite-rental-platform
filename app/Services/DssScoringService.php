<?php

namespace App\Services;

use App\Models\DssCriteria;
use App\Models\DssScore;
use App\Models\Property;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Support\Collection;

class DssScoringService
{
    /**
     * Score every available rental space against the renter's stated
     * preferences, using the currently active admin-configured weights.
     * Returns a ranked collection (highest score first), each entry:
     * ['space' => RentalSpace, 'score' => float, 'breakdown' => array, 'reasons' => array]
     */
    public function recommend(User $tenant, array $preferences, bool $persist = true): Collection
    {
        $weights = $this->activeWeights();

        $spaces = RentalSpace::query()
            ->with(['property.location', 'property.amenities'])
            ->where('status', 'available')
            ->whereHas('property', fn ($q) => $q->where('verification_status', 'verified')->available())
            ->get();

        $results = $spaces->map(function (RentalSpace $space) use ($preferences, $weights, $tenant, $persist) {
            $breakdown = [
                'budget' => $this->scoreBudget($space, $preferences),
                'location' => $this->scoreLocation($space, $preferences),
                'amenities' => $this->scoreAmenities($space, $preferences),
                'property_type' => $this->scorePropertyType($space, $preferences),
                'capacity' => $this->scoreCapacity($space, $preferences),
                'distance' => $this->scoreDistance($space, $preferences),
                'furnishing' => $this->scoreFurnishing($space, $preferences),
            ];

            $total = 0;
            $weightedBreakdown = [];
            foreach ($breakdown as $key => $score) {
                $weight = $weights[$key] ?? 0;
                $weighted = round(($score * $weight) / 100, 2);
                $total += $weighted;
                $weightedBreakdown[$key] = [
                    'score' => $score,
                    'weight' => $weight,
                    'weighted' => $weighted,
                ];
            }
            $total = round($total, 2);

            $reasons = $this->buildReasons($space, $preferences, $breakdown);

            if ($persist) {
                DssScore::create([
                    'user_id' => $tenant->id,
                    'property_id' => $space->property_id,
                    'rental_space_id' => $space->id,
                    'total_score' => $total,
                    'criteria_breakdown' => $weightedBreakdown,
                    'reasons' => $reasons,
                    'preferences_snapshot' => $preferences,
                ]);
            }

            return [
                'space' => $space,
                'property' => $space->property,
                'score' => $total,
                'breakdown' => $weightedBreakdown,
                'reasons' => $reasons,
            ];
        });

        return $results->sortByDesc('score')->values();
    }

    /**
     * @return array<string, float> criterion key => weight percentage
     */
    protected function activeWeights(): array
    {
        return DssCriteria::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function (DssCriteria $criterion) {
                $weight = $criterion->activeWeight();

                return [$criterion->key => $weight ? (float) $weight->weight_percentage : 0];
            })
            ->all();
    }

    // ── Individual criterion scorers, each 0-100 ────────────────────────

    protected function scoreBudget(RentalSpace $space, array $prefs): float
    {
        $budget = $prefs['max_budget'] ?? null;
        if (! $budget) {
            return 100;
        }

        $rent = (float) $space->monthly_rent;
        if ($rent <= $budget) {
            return 100;
        }

        $overBy = ($rent - $budget) / $budget;

        return max(0, round(100 - ($overBy * 100), 2));
    }

    protected function scoreLocation(RentalSpace $space, array $prefs): float
    {
        $preferredLocationId = $prefs['location_id'] ?? null;
        if (! $preferredLocationId) {
            return 100;
        }

        return $space->property->location_id == $preferredLocationId ? 100 : 30;
    }

    protected function scoreAmenities(RentalSpace $space, array $prefs): float
    {
        $required = $prefs['amenity_ids'] ?? [];
        if (empty($required)) {
            return 100;
        }

        $available = $space->property->amenities->pluck('id')->all();
        $matched = count(array_intersect($required, $available));

        return round(($matched / count($required)) * 100, 2);
    }

    protected function scorePropertyType(RentalSpace $space, array $prefs): float
    {
        $preferredType = $prefs['property_type'] ?? null;
        if (! $preferredType) {
            return 100;
        }

        return $space->property->property_type === $preferredType ? 100 : 0;
    }

    protected function scoreCapacity(RentalSpace $space, array $prefs): float
    {
        $occupants = $prefs['number_of_occupants'] ?? null;
        $requiredBedrooms = $prefs['required_bedrooms'] ?? null;

        $capacityOk = ! $occupants || $space->total_capacity >= $occupants;
        $bedroomsOk = ! $requiredBedrooms || $space->bedrooms >= $requiredBedrooms;

        if ($capacityOk && $bedroomsOk) {
            return 100;
        }
        if ($capacityOk && ! $bedroomsOk) {
            return 60;
        }

        return 0;
    }

    protected function scoreDistance(RentalSpace $space, array $prefs): float
    {
        $refLat = $prefs['reference_latitude'] ?? null;
        $refLng = $prefs['reference_longitude'] ?? null;
        $maxDistanceKm = $prefs['max_distance_km'] ?? null;

        $propertyLat = $space->property->latitude;
        $propertyLng = $space->property->longitude;

        if (! $refLat || ! $refLng || ! $propertyLat || ! $propertyLng || ! $maxDistanceKm) {
            return 100; // not enough data to penalize fairly — stay neutral
        }

        $distanceKm = $this->haversineDistanceKm((float) $refLat, (float) $refLng, (float) $propertyLat, (float) $propertyLng);

        if ($distanceKm <= $maxDistanceKm) {
            return 100;
        }

        $overBy = ($distanceKm - $maxDistanceKm) / $maxDistanceKm;

        return max(0, round(100 - ($overBy * 100), 2));
    }

    protected function scoreFurnishing(RentalSpace $space, array $prefs): float
    {
        $preference = $prefs['furnishing'] ?? null; // 'furnished' | 'unfurnished' | null
        if (! $preference) {
            return 100;
        }

        $wantsFurnished = $preference === 'furnished';

        return $space->is_furnished === $wantsFurnished ? 100 : 40;
    }

    protected function haversineDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    /**
     * Plain-language explanation of the score, built from the same numbers
     * that drove it — never a separate, potentially-inconsistent narrative.
     */
    protected function buildReasons(RentalSpace $space, array $prefs, array $breakdown): array
    {
        $reasons = [];

        if (($prefs['max_budget'] ?? null) && $breakdown['budget'] >= 100) {
            $reasons[] = 'Within your ₱'.number_format($prefs['max_budget']).' monthly budget.';
        } elseif (($prefs['max_budget'] ?? null) && $breakdown['budget'] < 100) {
            $reasons[] = 'Slightly above your stated budget.';
        }

        if (($prefs['location_id'] ?? null) && $breakdown['location'] >= 100) {
            $reasons[] = 'Located in your preferred city/municipality.';
        }

        if (! empty($prefs['amenity_ids'] ?? [])) {
            $reasons[] = $breakdown['amenities'] >= 100
                ? 'Has all the amenities you require.'
                : round($breakdown['amenities']).'% of your required amenities are available.';
        }

        if (($prefs['property_type'] ?? null) && $breakdown['property_type'] >= 100) {
            $reasons[] = 'Matches your preferred property type.';
        }

        if ($breakdown['capacity'] >= 100 && (($prefs['number_of_occupants'] ?? null) || ($prefs['required_bedrooms'] ?? null))) {
            $reasons[] = 'Has sufficient room capacity for your household.';
        }

        if (($prefs['furnishing'] ?? null) && $breakdown['furnishing'] >= 100) {
            $reasons[] = $prefs['furnishing'] === 'furnished' ? 'Comes furnished, as you requested.' : 'Unfurnished, as you requested.';
        }

        if (empty($reasons)) {
            $reasons[] = 'General match based on your preferences.';
        }

        return $reasons;
    }
}
