<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'receipt_number',
        'student_id',
        'fee_type_id',
        'account_id',
        'academic_year_id',
        'payment_date',
        'month',
        'year',
        'amount',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->receipt_number) {
                $payment->receipt_number = static::generateReceiptNumber();
            }
        });

        static::created(function ($payment) {
            $payment->account->updateBalance();
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', now())->count() + 1;
        return 'KWT/' . $date . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getMonthNameAttribute(): ?string
    {
        if (!$this->month)
            return null;
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $months[$this->month] ?? null;
    }
}
