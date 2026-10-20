<?php

namespace Tests\Feature;

use App\Livewire\Transactions;
use App\Models\Account;
use App\Models\Period;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionNoteTest extends TestCase
{
    use RefreshDatabase;

    private Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->travelTo('2026-08-20');

        $period = Period::create(['start_date' => '2026-08-15', 'end_date' => '2026-09-14', 'is_current' => true]);

        $this->transaction = Transaction::create([
            'period_id' => $period->id,
            'account_id' => Account::where('name', 'amex')->firstOrFail()->id,
            'date' => '2026-08-18',
            'description' => 'JUMBO UTRECHT FOODMARK',
            'amount' => -12.34,
            'source' => 'import',
        ]);
    }

    public function test_a_note_can_be_saved_inline(): void
    {
        Livewire::test(Transactions::class)
            ->call('updateNote', $this->transaction->id, 'Groceries for the weekend')
            ->assertHasNoErrors();

        $this->assertSame('Groceries for the weekend', $this->transaction->fresh()->notes);
    }

    public function test_the_note_is_trimmed(): void
    {
        Livewire::test(Transactions::class)
            ->call('updateNote', $this->transaction->id, '  Lunch  ');

        $this->assertSame('Lunch', $this->transaction->fresh()->notes);
    }

    public function test_a_blank_note_clears_the_existing_note(): void
    {
        $this->transaction->update(['notes' => 'Old note']);

        Livewire::test(Transactions::class)
            ->call('updateNote', $this->transaction->id, '   ');

        $this->assertNull($this->transaction->fresh()->notes);
    }

    public function test_a_note_longer_than_1000_characters_is_rejected(): void
    {
        Livewire::test(Transactions::class)
            ->call('updateNote', $this->transaction->id, str_repeat('a', 1001))
            ->assertHasErrors('note');

        $this->assertNull($this->transaction->fresh()->notes);
    }

    public function test_saving_a_note_does_not_touch_other_fields(): void
    {
        Livewire::test(Transactions::class)
            ->call('updateNote', $this->transaction->id, 'Checked');

        $fresh = $this->transaction->fresh();

        $this->assertSame('JUMBO UTRECHT FOODMARK', $fresh->description);
        $this->assertEquals(-12.34, (float) $fresh->amount);
        $this->assertNull($fresh->category_id);
    }

    public function test_saved_notes_are_rendered_in_the_transaction_list(): void
    {
        $this->transaction->update(['notes' => 'Weekly shop']);

        Livewire::test(Transactions::class)->assertSee('Weekly shop');
    }
}
