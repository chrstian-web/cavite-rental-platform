<?php

namespace App\Services;

use App\Models\AdminActivityArchive;
use App\Models\OwnerVerification;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminActivityService
{
    public const TYPE_USER_REGISTRATION = 'user_registration';
    public const TYPE_PROPERTY_SUBMISSION = 'property_submission';
    public const TYPE_OWNER_VERIFICATION = 'owner_verification';

    public function recent(User $admin, int $limit = 8, bool $archived = false): Collection
    {
        $archivedKeys = AdminActivityArchive::query()
            ->where('admin_id', $admin->id)
            ->get(['activity_type', 'activity_id'])
            ->mapWithKeys(fn (AdminActivityArchive $item) => [$this->key($item->activity_type, $item->activity_id) => true]);

        return $this->sourceActivities()
            ->filter(function (array $item) use ($archived, $archivedKeys) {
                $isArchived = $archivedKeys->has($item['archive_key']);

                return $archived ? $isArchived : ! $isArchived;
            })
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    public function archive(User $admin, string $type, int $activityId): void
    {
        AdminActivityArchive::updateOrCreate(
            [
                'admin_id' => $admin->id,
                'activity_type' => $type,
                'activity_id' => $activityId,
            ],
            ['archived_at' => now()],
        );
    }

    public function restore(User $admin, string $type, int $activityId): void
    {
        AdminActivityArchive::query()
            ->where('admin_id', $admin->id)
            ->where('activity_type', $type)
            ->where('activity_id', $activityId)
            ->delete();
    }

    public function isSupportedType(string $type): bool
    {
        return in_array($type, [
            self::TYPE_USER_REGISTRATION,
            self::TYPE_PROPERTY_SUBMISSION,
            self::TYPE_OWNER_VERIFICATION,
        ], true);
    }

    private function sourceActivities(): Collection
    {
        $signups = User::with('role')->latest()->take(100)->get()
            ->filter(fn (User $user) => $user->role?->slug !== 'super_admin')
            ->map(fn (User $user) => [
                'at' => $user->created_at,
                'text' => trim($user->first_name.' '.$user->last_name).' registered as '.str($user->role?->slug ?? 'user')->replace('_', ' ')->lower(),
                'url' => route('admin.users.index', ['search' => $user->email]),
                'archive_type' => self::TYPE_USER_REGISTRATION,
                'archive_id' => $user->id,
                'archive_key' => $this->key(self::TYPE_USER_REGISTRATION, $user->id),
            ]);

        $properties = Property::with('owner')->latest()->take(100)->get()
            ->map(fn (Property $property) => [
                'at' => $property->created_at,
                'text' => '"'.$property->name.'" submitted by '.trim(($property->owner?->first_name ?? '').' '.($property->owner?->last_name ?? '')),
                'url' => route('admin.properties.index'),
                'archive_type' => self::TYPE_PROPERTY_SUBMISSION,
                'archive_id' => $property->id,
                'archive_key' => $this->key(self::TYPE_PROPERTY_SUBMISSION, $property->id),
            ]);

        $verifications = OwnerVerification::with('user')
            ->whereNotNull('submitted_at')
            ->whereHas('user', fn ($query) => $query->withoutTrashed())
            ->latest('submitted_at')
            ->take(100)
            ->get()
            ->map(fn (OwnerVerification $verification) => [
                'at' => $verification->submitted_at,
                'text' => trim(($verification->user->first_name ?? '').' '.($verification->user->last_name ?? '')).' submitted owner verification',
                'url' => route('admin.owner-verifications.show', $verification),
                'archive_type' => self::TYPE_OWNER_VERIFICATION,
                'archive_id' => $verification->id,
                'archive_key' => $this->key(self::TYPE_OWNER_VERIFICATION, $verification->id),
            ]);

        return $signups->concat($properties)->concat($verifications);
    }

    private function key(string $type, int $id): string
    {
        return $type.':'.$id;
    }
}
