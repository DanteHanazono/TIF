<?php

namespace Tests\Feature\Artists;

use App\Models\Artist;
use App\Models\Performance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArtistFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_can_be_created_from_the_form(): void
    {
        Livewire::test('artist.form')
            ->set('name', 'Radiohead')
            ->call('save')
            ->assertRedirect(route('artists.index'))
            ->assertHasNoErrors();

        $this->assertDatabaseHas('artists', [
            'name' => 'Radiohead',
        ]);
    }

    public function test_artist_can_be_updated_from_the_form(): void
    {
        $artist = Artist::query()->create([
            'name' => 'The Beatles',
        ]);

        Livewire::test('artist.form', ['artist' => $artist])
            ->assertSet('name', 'The Beatles')
            ->set('name', 'The Beatles Reimagined')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('artists', [
            'id' => $artist->id,
            'name' => 'The Beatles Reimagined',
        ]);
    }

    public function test_artist_name_is_required(): void
    {
        Livewire::test('artist.form')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);

        $this->assertDatabaseCount('artists', 0);
    }

    public function test_artist_create_page_can_be_rendered(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('artists.create'))
            ->assertOk()
            ->assertSee('Crear artista');
    }

    public function test_artist_edit_page_can_be_rendered(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $artist = Artist::query()->create([
            'name' => 'Radiohead',
        ]);

        $this->actingAs($user)
            ->get(route('artists.edit', $artist))
            ->assertOk()
            ->assertSee('Editar artista')
            ->assertSee('Día');
    }

    public function test_performance_can_be_registered_from_the_artist_form(): void
    {
        $artist = Artist::query()->create([
            'name' => 'Aimer',
        ]);

        Livewire::test('artist.form', ['artist' => $artist])
            ->assertSee('Registro de presentación')
            ->set('event_days', ['1', '3'])
            ->set('performance_rows', [
                1 => [
                    'stage' => 'HOT STAGE',
                    'start_time' => '19:00',
                    'end_time' => '20:00',
                ],
                3 => [
                    'stage' => 'SKY STAGE',
                    'start_time' => '18:00',
                    'end_time' => '19:30',
                ],
            ])
            ->call('savePerformance')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('performances', [
            'artist_id' => $artist->id,
            'event_day' => 1,
            'stage' => 'HOT STAGE',
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
        ]);

        $this->assertDatabaseHas('performances', [
            'artist_id' => $artist->id,
            'event_day' => 3,
            'stage' => 'SKY STAGE',
            'start_time' => '18:00:00',
            'end_time' => '19:30:00',
        ]);
    }

    public function test_existing_performances_are_loaded_and_can_be_updated_from_the_artist_form(): void
    {
        $artist = Artist::query()->create([
            'name' => 'Aimer',
        ]);

        $performance = Performance::query()->create([
            'artist_id' => $artist->id,
            'event_day' => 2,
            'stage' => 'HOT STAGE',
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
        ]);

        Livewire::test('artist.form', ['artist' => $artist])
            ->assertSet('event_days', [2])
            ->assertSet('performance_rows.2.stage', 'HOT STAGE')
            ->assertSet('performance_rows.2.start_time', '19:00')
            ->assertSet('performance_rows.2.end_time', '20:00')
            ->set('performance_rows.2.stage', 'SKY STAGE')
            ->set('performance_rows.2.start_time', '21:00')
            ->set('performance_rows.2.end_time', '22:00')
            ->call('savePerformance')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('performances', [
            'id' => $performance->id,
            'artist_id' => $artist->id,
            'event_day' => 2,
            'stage' => 'SKY STAGE',
            'start_time' => '21:00:00',
            'end_time' => '22:00:00',
        ]);

        $this->assertDatabaseCount('performances', 1);
    }

    public function test_performance_requires_an_artist_and_valid_data(): void
    {
        $artist = Artist::query()->create([
            'name' => 'YOASOBI',
        ]);

        Livewire::test('artist.form', ['artist' => $artist])
            ->set('event_days', ['1'])
            ->set('performance_rows', [
                1 => [
                    'stage' => '',
                    'start_time' => '',
                    'end_time' => '',
                ],
            ])
            ->call('savePerformance')
            ->assertHasErrors([
                'performance_rows.1.stage' => 'required',
                'performance_rows.1.start_time' => 'required',
                'performance_rows.1.end_time' => 'required',
            ]);

        $this->assertDatabaseCount('performances', 0);
    }

    public function test_selecting_days_requires_at_least_one_day(): void
    {
        $artist = Artist::query()->create([
            'name' => 'LiSA',
        ]);

        Livewire::test('artist.form', ['artist' => $artist])
            ->set('event_days', [])
            ->call('savePerformance')
            ->assertHasErrors([
                'event_days' => 'required',
            ]);

        $this->assertDatabaseCount('performances', 0);
    }
}
