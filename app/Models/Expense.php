<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'account_id',
        'academic_year_id',
        'expense_date',
        'amount',
        'vendor',
        'description',
        'receipt_file',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (!$expense->expense_number) {
                $expense->expense_number = static::generateExpenseNumber();
            }
        });

        static::created(function ($expense) {
            $expense->account->updateBalance();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateExpenseNumber(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', now())->count() + 1;
        return 'EXP/' . $date . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
