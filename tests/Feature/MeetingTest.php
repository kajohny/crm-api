<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Manager;
use App\Models\Meeting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MeetingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company_one;
    private Company $company_two;
    private Manager $manager_one;
    private Manager $manager_two;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-23T00:00:00Z'));

        $this->company_one = Company::create([
            'name' => 'Company 1',
            'bin' => '123456789012',
            'phone' => '+77000000001',
        ]);

        $this->company_two = Company::create([
            'name' => 'Company 2',
            'bin' => '123456789013',
            'phone' => '+77000000002',
        ]);

        $this->manager_one = Manager::create([
            'name' => 'Manager 1',
        ]);

        $this->manager_two = Manager::create([
            'name' => 'Manager 2',
        ]);
    }

    public function test_manager_cannot_have_overlapping_meetings(): void
    {
        $this->postJson('/meetings', [
            'company_id' => $this->company_one->id,
            'manager_id' => $this->manager_one->id,
            'starts_at' => '2026-11-24T05:00:00Z',
        ])->assertCreated();

        $this->postJson('/meetings', [
            'company_id' => $this->company_two->id,
            'manager_id' => $this->manager_one->id,
            'starts_at' => '2026-11-24T05:30:00Z',
        ])->assertConflict();

        $this->assertDatabaseCount('meetings', 1);
    }

    public function test_meeting_must_fit_within_working_hours(): void
    {
        $this->postJson('/meetings', [
            'company_id' => $this->company_one->id,
            'manager_id' => $this->manager_one->id,
            'starts_at' => '2026-11-24T12:00:00Z',
        ])->assertCreated();

        $this->postJson('/meetings', [
            'company_id' => $this->company_two->id,
            'manager_id' => $this->manager_one->id,
            'starts_at' => '2026-11-24T12:30:00Z',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_company_cannot_have_two_active_meetings(): void
    {
        $this->postJson('/meetings', [
            'company_id' => $this->company_one->id,
            'manager_id' => $this->manager_one->id,
            'starts_at' => '2026-11-24T05:00:00Z',
        ])->assertCreated();

        $this->postJson('/meetings', [
            'company_id' => $this->company_one->id,
            'manager_id' => $this->manager_two->id,
            'starts_at' => '2026-11-24T07:00:00Z',
        ])->assertConflict();

        $this->assertDatabaseCount('meetings', 1);
    }

    public function test_meeting_set_requires_meeting(): void
    {
        $this->postJson('/calls', [
            'company_id' => $this->company_one->id,
            'result' => 'meeting_set',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['meeting_id']);

        $this->assertDatabaseCount('calls', 0);
    }

    public function test_rescheduling_preserves_original_start_time(): void
    {
        $response = $this->postJson('/meetings', [
            'company_id' => $this->company_one->id,
            'manager_id' => $this->manager_one->id,
            'starts_at' => '2026-11-24T05:00:00Z',
        ])->assertCreated();

        $meeting_id = $response->json('id');

        $this->patchJson("/meetings/{$meeting_id}", [
            'starts_at' => '2026-11-24T07:00:00Z',
        ])->assertOk();

        $meeting = Meeting::findOrFail($meeting_id);

        $this->assertSame(
            '2026-11-24T05:00:00.000000Z',
            $meeting->original_starts_at->toISOString()
        );

        $this->assertSame(
            '2026-11-24T07:00:00.000000Z',
            $meeting->starts_at->toISOString()
        );
    }
}
