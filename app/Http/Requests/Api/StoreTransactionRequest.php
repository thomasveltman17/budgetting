<?php

namespace App\Http\Requests\Api;

use App\Models\Account;
use App\Models\Category;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    private ?Account $resolvedAccount = null;

    private ?Category $resolvedCategory = null;

    /**
     * Access is already enforced by the AuthenticateApiToken middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'account' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:expense,income'],
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('account') || $validator->errors()->has('category')) {
                    return;
                }

                $this->resolvedAccount = $this->findAccount($this->string('account')->trim()->toString());

                if (! $this->resolvedAccount) {
                    $validator->errors()->add('account', 'Unknown account. Use one of: '.Account::orderBy('label')->pluck('label')->implode(', ').'.');
                }

                if ($this->filled('category')) {
                    $this->resolvedCategory = $this->findCategory($this->string('category')->trim()->toString());

                    if (! $this->resolvedCategory) {
                        $validator->errors()->add('category', 'Unknown category. Use one of: '.$this->activeCategories()->pluck('name')->implode(', ').'.');
                    }
                }
            },
        ];
    }

    public function account(): Account
    {
        return $this->resolvedAccount;
    }

    public function category(): ?Category
    {
        return $this->resolvedCategory;
    }

    public function transactionDate(): CarbonImmutable
    {
        return $this->filled('date')
            ? CarbonImmutable::parse($this->input('date'))->startOfDay()
            : CarbonImmutable::today();
    }

    /**
     * Expenses are stored as negative amounts, income as positive.
     */
    public function signedAmount(): float
    {
        $amount = abs((float) $this->input('amount'));

        return $this->input('type') === 'income' ? $amount : -$amount;
    }

    /**
     * Shortcuts often sends currency-formatted text (e.g. "€ 1.234,56"), so it's
     * normalised to a plain decimal before validation. The sign is dropped
     * because the `type` field decides whether it's an expense or income.
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if (is_int($amount) || is_float($amount)) {
            $this->merge(['amount' => abs($amount)]);

            return;
        }

        if (! is_string($amount)) {
            return;
        }

        $cleaned = preg_replace('/[^\d,.]/', '', $amount);
        $lastComma = strrpos($cleaned, ',');
        $lastDot = strrpos($cleaned, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
        } else {
            $cleaned = str_replace(',', '', $cleaned);
        }

        $this->merge(['amount' => $cleaned]);
    }

    /**
     * Matches on id, name or label first, then on a label/name contained in the
     * input so Wallet card names like "Revolut Visa" still resolve.
     */
    private function findAccount(string $identifier): ?Account
    {
        $needle = Str::lower($identifier);
        $accounts = Account::all();

        return $accounts->first(fn (Account $account): bool => in_array($needle, [(string) $account->id, Str::lower($account->name), Str::lower($account->label)], true))
            ?? $accounts->first(fn (Account $account): bool => Str::contains($needle, [Str::lower($account->name), Str::lower($account->label)]));
    }

    private function findCategory(string $identifier): ?Category
    {
        $needle = Str::lower($identifier);

        return $this->activeCategories()
            ->first(fn (Category $category): bool => in_array($needle, [(string) $category->id, Str::lower($category->name)], true));
    }

    /**
     * @return Collection<int, Category>
     */
    private function activeCategories(): Collection
    {
        return Category::where('is_archived', false)->orderBy('sort_order')->get();
    }
}
