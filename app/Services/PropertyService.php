<?php

namespace App\Services;

use App\Models\Property;
use App\Models\User;
use App\Notifications\NewPropertySubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyService
{
    /**
     * Create a property, its amenity links, and any uploaded images
     * inside a single transaction so a partial failure never leaves
     * an orphaned property with no images/amenities.
     */
    public function create(array $data, array $amenityIds, array $images, int $ownerId): Property
    {
        return DB::transaction(function () use ($data, $amenityIds, $images, $ownerId) {
            $property = Property::create([
                ...$data,
                'owner_id' => $ownerId,
                'slug' => $this->uniqueSlug($data['name']),
            ]);

            if (! empty($amenityIds)) {
                $property->amenities()->sync($amenityIds);
            }

            $this->storeImages($property, $images);

            // Every new listing starts unverified — let admins know one is waiting.
            User::whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))
                ->get()
                ->each(fn (User $admin) => $admin->notify(new NewPropertySubmittedNotification($property)));

            return $property->fresh(['images', 'amenities']);
        });
    }

    public function update(Property $property, array $data, array $amenityIds, array $images): Property
    {
        return DB::transaction(function () use ($property, $data, $amenityIds, $images) {
            if ($data['name'] !== $property->name) {
                $data['slug'] = $this->uniqueSlug($data['name'], $property->id);
            }

            $property->update($data);
            $property->amenities()->sync($amenityIds);
            $this->storeImages($property, $images);

            return $property->fresh(['images', 'amenities']);
        });
    }

    public function delete(Property $property): void
    {
        DB::transaction(function () use ($property) {
            foreach ($property->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            $property->delete(); // soft delete
        });
    }

    public function deleteImage(Property $property, int $imageId): void
    {
        $image = $property->images()->findOrFail($imageId);
        Storage::disk('public')->delete($image->path);
        $image->delete();
    }

    /**
     * @param  UploadedFile[]  $images
     */
    protected function storeImages(Property $property, array $images): void
    {
        if (empty($images)) {
            return;
        }

        $hasCover = $property->images()->where('is_cover', true)->exists();
        $nextSortOrder = (int) $property->images()->max('sort_order');

        foreach ($images as $index => $file) {
            $path = $file->store("properties/{$property->id}", 'public');

            $property->images()->create([
                'path' => $path,
                'is_cover' => ! $hasCover && $index === 0,
                'sort_order' => ++$nextSortOrder,
            ]);
        }
    }

    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (
            Property::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
