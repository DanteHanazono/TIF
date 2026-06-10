<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Performance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeTimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_the_public_timetable(): void
    {
        $artist = Artist::query()->create(['name' => 'Aimer']);

        Performance::query()->create([
            'artist_id' => $artist->id,
            'event_day' => 1,
            'stage' => 'HOT STAGE',
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Día 1')
            ->assertSee('Día 2')
            ->assertSee('Día 3')
            ->assertSee('Aimer')
            ->assertSee('HOT STAGE')
            ->assertSee('19:00 - 20:00');
    }

    public function test_timetable_is_ordered_by_start_time_for_the_active_day(): void
    {
        $lateArtist = Artist::query()->create(['name' => 'Late Artist']);
        $earlyArtist = Artist::query()->create(['name' => 'Early Artist']);
        $otherDayArtist = Artist::query()->create(['name' => 'Other Day Artist']);

        Performance::query()->create([
            'artist_id' => $lateArtist->id,
            'event_day' => 1,
            'stage' => 'SKY STAGE',
            'start_time' => '21:00:00',
            'end_time' => '22:00:00',
        ]);

        Performance::query()->create([
            'artist_id' => $earlyArtist->id,
            'event_day' => 1,
            'stage' => 'DREAM STAGE',
            'start_time' => '17:30:00',
            'end_time' => '18:30:00',
        ]);

        Performance::query()->create([
            'artist_id' => $otherDayArtist->id,
            'event_day' => 2,
            'stage' => 'HOT STAGE',
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
        ]);

        Livewire::test('home')
            ->assertSeeInOrder(['17:30', 'Early Artist', '21:00', 'Late Artist'])
            ->assertDontSee('Other Day Artist')
            ->call('setActiveDay', 2)
            ->assertSee('Other Day Artist')
            ->assertDontSee('Early Artist');
    }
}
