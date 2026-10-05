<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Services\PeriodService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dates_before_the_15th_belong_to_the_period_started_last_month(): void
    {
        $period = app(PeriodService::class)->findOrCreateForDate(Carbon::parse('2026-03-14'));

        $this->assertSame('2026-02-15', $period->start_date->toDateString());
        $this->assertSame('2026-03-14', $period->end_date->toDateString());
    }

    public function test_end_of_month_dates_do_not_overflow_into_a_two_month_period(): void
    {
        $period = app(PeriodService::class)->findOrCreateForDate(Carbon::parse('2027-01-31'));

        $this->assertSame('2027-01-15', $period->start_date->toDateString());
        $this->assertSame('2027-02-14', $period->end_date->toDateString());
    }

    public function test_current_period_is_correct_on_the_last_day_of_a_31_day_month(): void
    {
        $this->travelTo('2026-08-31');

        $period = app(PeriodService::class)->ensureCurrentPeriodExists();

        $this->assertSame('2026-08-15', $period->start_date->toDateString());
        $this->assertSame('2026-09-14', $period->end_date->toDateString());
        $this->assertTrue($period->is_current);
        $this->assertSame(1, Period::count());
    }
}
