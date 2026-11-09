<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class RentalContract extends Model
{
    protected $fillable = [
        'rental_application_id', 'user_id', 'owner_id', 'property_id', 'rental_space_id',
        'monthly_rent', 'security_deposit', 'advance_payment', 'start_date', 'end_date',
        'terms_and_conditions', 'contract_pdf_path', 'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_rent' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'advance_payment' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RentalApplication::class, 'rental_application_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class)->withTrashed();
    }

    public function rentalSpace(): BelongsTo
    {
        return $this->belongsTo(RentalSpace::class)->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Every payment the tenant has made for this tenancy, oldest first.
     *
     * The reservation fee is paid before the contract exists, so it is linked to
     * the rental application instead of the contract and is not part of
     * $this->payments. This prepends it so both tenant and owner see the full
     * history. Display only: do not use this for down-payment/activation checks.
     */
    public function paymentHistory(): Collection
    {
        $reservation = $this->application?->reservationFeePayment;

        // Build a fresh collection: prepend() mutates in place, which would leak the
        // reservation fee into the loaded $this->payments relation.
        return collect($reservation ? [$reservation] : [])
            ->concat($this->payments)
            ->values();
    }

    /**
     * Amounts that must be paid up front, keyed by payment type. A zero
     * amount means that part is not required for this contract.
     *
     * @return array<string, float>
     */
    public function downPaymentRequirements(): array
    {
        return [
            Payment::TYPE_SECURITY_DEPOSIT => (float) $this->security_deposit,
            Payment::TYPE_ADVANCE_PAYMENT => (float) $this->advance_payment,
        ];
    }

    /**
     * Required down payment types that do not yet have an approved ('paid') payment.
     *
     * @return string[]
     */
    public function unpaidDownPaymentTypes(): array
    {
        $paid = $this->payments()
            ->whereIn('payment_type', Payment::DOWN_PAYMENT_TYPES)
            ->where('status', 'paid')
            ->pluck('payment_type')
            ->all();

        $unpaid = [];
        foreach ($this->downPaymentRequirements() as $type => $amount) {
            if ($amount > 0 && ! in_array($type, $paid, true)) {
                $unpaid[] = $type;
            }
        }

        return $unpaid;
    }

    public function hasSettledDownPayments(): bool
    {
        return $this->unpaidDownPaymentTypes() === [];
    }

    public function downPaymentBlockedMessage(): string
    {
        $requirements = $this->downPaymentRequirements();

        $parts = collect($this->unpaidDownPaymentTypes())->map(
            fn (string $type) => str($type)->replace('_', ' ')->title().' (₱'.number_format($requirements[$type], 2).')'
        );

        return 'This contract cannot be activated until the down payment is paid and approved: '.$parts->implode(', ').'.';
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
