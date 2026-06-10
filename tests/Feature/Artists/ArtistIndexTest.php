<?php

namespace Tests\Feature\Artists;

use App\Models\Artist;
use App\Models\Performance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArtistIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_index_page_can_be_rendered(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $artist = Artist::query()->create(['name' => 'Aimer']);
        Artist::query()->create(['name' => 'YOASOBI']);

        Performance::query()->create([
            'artist_id' => $artist->id,
            'event_day' => 1,
            'stage' => 'HOT STAGE',
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
        ]);

        Performance::query()->create([
            'artist_id' => $artist->id,
            'event_day' => 3,
            'stage' => 'SKY STAGE',
            'start_time' => '18:00:00',
            'end_time' => '19:30:00',
        ]);

        $this->actingAs($user)
            ->get(route('artists.index'))
            ->assertOk()
            ->assertSee('Artistas')
            ->assertSee('Aimer')
            ->assertSee('YOASOBI')
            ->assertSee('Día 1')
            ->assertSee('HOT STAGE')
            ->assertSee('19:00 - 20:00')
            ->assertSee('Día 3')
            ->assertSee('SKY STAGE')
            ->assertSee('18:00 - 19:30')
            ->assertSee('Editar')
            ->assertSee('Eliminar');
    }

    public function test_artist_can_be_deleted_from_the_index(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $artist = Artist::query()->create(['name' => 'Aimer']);

        $this->actingAs($user);

        Livewire::test('artist.index')
            ->call('prepareDelete', $artist->id)
            ->call('deleteArtist')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('artists', [
            'id' => $artist->id,
        ]);
    }

    public function test_artists_are_listed_alphabetically_by_name(): void
    {
        Artist::query()->create(['name' => 'YOASOBI']);
        Artist::query()->create(['name' => 'Aimer']);
        Artist::query()->create(['name' => 'Babymetal']);

        Livewire::test('artist.index')
            ->assertSeeInOrder(['Aimer', 'Babymetal', 'YOASOBI']);
    }

    public function test_artists_can_be_searched_by_name(): void
    {
        Artist::query()->create(['name' => 'Aimer']);
        Artist::query()->create(['name' => 'YOASOBI']);
        Artist::query()->create(['name' => 'Babymetal']);

        Livewire::test('artist.index')
            ->set('search', 'aime')
            ->assertSee('Aimer')
            ->assertDontSee('YOASOBI')
            ->assertDontSee('Babymetal');
    }
}
