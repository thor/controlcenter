<?php

namespace Tests\Feature;

use App\Helpers\VatsimRating;
use App\Models\Booking;
use App\Models\Endorsement;
use App\Models\Handover;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    // TODO: Use configuration to assign the relevant databases
    protected $connectionsToTransact = [
        'mysql',
        'sqlite-testing',
        'mysql-handover'
    ];

    private function assertCreateBookingAvailable(User $controller)
    {
        $checkBrowser = $this->actingAs($controller)->followingRedirects()
            ->get(route('booking'));

        $randomPosition = Position::whereRating($controller->rating)->whereNotNull("name")->inRandomOrder()->first();
        $checkBrowser->assertSee($randomPosition->name);
        $checkBrowser->assertSeeText("Create Booking");
    }

    private function createBooking(User $controller)
    {
        $lastBooking = Booking::all()->last();

        $startDate = new Carbon(fake()->dateTimeBetween("tomorrow", "+2 months"));
        $endDate = $startDate->copy()->addHours(2)->addMinutes(30);
        $bookingRequest = [
            'date' => $startDate->format("d/m/Y"),
            'start_at' => $startDate->format("H:i"),
            'end_at' => $endDate->format("H:i"),
            'position' => Position::whereRating($controller->rating)->whereNotNull("name")->inRandomOrder()->first()->callsign
        ];
        $response = $this->actingAs($controller)->followingRedirects()->post(
            '/booking/store',
            $bookingRequest
        );

        $this->assertNotSame($lastBooking, Booking::all()->last());
        return $response;
    }


    /**
     * Validate that a controller with an S1 rating can create a booking.
     */
    public function test_active_s1_can_create_booking()
    {
        $controller = User::factory()->create(['id' => fake()->numberBetween(100)]);
        Handover::factory()->create([
            'id' => $controller->id,
            'atc_active' => true,
            'rating' => VatsimRating::S1->value
        ])->save();

        Endorsement::factory()->create([
            'user_id' => $controller->id,
            'type' => "S1",
            'valid_to' => NULL
        ])->save();
        $this->assertTrue($controller->hasActiveEndorsement("S1", true));

        $this->assertCreateBookingAvailable($controller);
        $response = $this->createBooking($controller)->assertValid();
        $response->assertValid();

    }

    public function test_active_s2_can_create_booking(): void
    {
        $controller = User::factory()->create(['id' => 10000003]);
        $controller->handover->atc_active = true;
        $this->assertCreateBookingAvailable($controller);

        $response = $this->createBooking($controller)->assertValid();
        $response->assertValid();
    }
}
