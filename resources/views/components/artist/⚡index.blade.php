<?php

use App\Models\Artist;
use Livewire\Component;

new class extends Component
{
    public ?int $deletingArtistId = null;

    public string $deletingArtistName = '';

    public string $search = '';

    public function render()
    {
        return $this->view([
            'artists' => Artist::query()
                ->with(['performances' => fn ($query) => $query->orderBy('event_day')->orderBy('stage')])
                ->when(
                    filled($this->search),
                    fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')
                )
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function prepareDelete(int $artistId): void
    {
        $artist = Artist::query()->findOrFail($artistId);

        $this->deletingArtistId = $artist->id;
        $this->deletingArtistName = $artist->name;
    }

    public function cancelDelete(): void
    {
        $this->reset('deletingArtistId', 'deletingArtistName');
    }

    public function deleteArtist(): void
    {
        if ($this->deletingArtistId === null) {
            return;
        }

        Artist::query()->findOrFail($this->deletingArtistId)->delete();

        $this->cancelDelete();

        session()->flash('status', 'Artista eliminado correctamente.');
    }
};
?>

<section class="w-full">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.7fr)_minmax(280px,0.9fr)]">
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-neutral-300 to-transparent opacity-70 dark:via-neutral-600"></div>

                <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-600 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                            Lista de artistas
                        </div>

                        <flux:heading size="lg" class="text-neutral-950 dark:text-neutral-50">
                            Artistas
                        </flux:heading>

                        <flux:subheading class="max-w-2xl text-neutral-600 dark:text-neutral-400">
                            Agrega, edita o elimina los artistas de tu interés.
                        </flux:subheading>
                    </div>

                    <a
                        href="{{ route('artists.create') }}"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-700 transition hover:border-neutral-300 hover:bg-neutral-100 hover:text-neutral-950 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-200 dark:hover:border-neutral-600 dark:hover:bg-zinc-700 dark:hover:text-white"
                    >
                        Nuevo artista
                    </a>
                </div>

                @if (session('status'))
                    <div class="mt-5 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mt-5 overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="border-b border-neutral-200 bg-neutral-50/70 p-4 dark:border-neutral-700 dark:bg-zinc-800/60">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            label="Buscar artista"
                            placeholder="Escribe un nombre..."
                            type="search"
                            class="dark:bg-zinc-950"
                        />
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead class="bg-neutral-50/80 dark:bg-zinc-800/60">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                        Nombre del artista
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-zinc-900">
                                @forelse ($artists as $artist)
                                    <tr wire:key="artist-{{ $artist->id }}" class="group transition hover:bg-neutral-50/80 dark:hover:bg-zinc-800/60">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-sm font-semibold text-neutral-500 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                                                    {{ mb_strtoupper(mb_substr($artist->name, 0, 1)) }}
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="truncate font-medium text-neutral-950 dark:text-neutral-50">
                                                        {{ $artist->name }}
                                                    </div>

                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @forelse ($artist->performances as $performance)
                                                            <span class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-600 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                                                                <span>Día {{ $performance->event_day }}</span>
                                                                <span class="h-1 w-1 rounded-full bg-neutral-400 dark:bg-neutral-500"></span>
                                                                <span>{{ $performance->stage }}</span>
                                                                <span class="h-1 w-1 rounded-full bg-neutral-400 dark:bg-neutral-500"></span>
                                                                <span>{{ str($performance->start_time)->substr(0, 5) }} - {{ str($performance->end_time)->substr(0, 5) }}</span>
                                                            </span>
                                                        @empty
                                                            <span class="inline-flex items-center rounded-full border border-dashed border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-400 dark:border-neutral-700 dark:text-neutral-500">
                                                                Sin presentaciones
                                                            </span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-5 py-4 text-right">
                                            <flux:dropdown position="bottom" align="end">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-neutral-200 bg-white text-lg leading-none text-neutral-500 transition hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-900 dark:border-neutral-700 dark:bg-zinc-900 dark:text-neutral-300 dark:hover:border-neutral-600 dark:hover:bg-zinc-800 dark:hover:text-neutral-50"
                                                    aria-label="Acciones de {{ $artist->name }}"
                                                >
                                                    ⋯
                                                </button>

                                                <flux:menu>
                                                    <flux:menu.item :href="route('artists.edit', $artist)" wire:navigate>
                                                        Editar
                                                    </flux:menu.item>

                                                    <flux:menu.item
                                                        as="button"
                                                        type="button"
                                                        x-data=""
                                                        x-on:click.prevent="$wire.prepareDelete({{ $artist->id }}); $dispatch('open-modal', 'confirm-artist-deletion')"
                                                    >
                                                        Eliminar
                                                    </flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-5 py-14 text-center">
                                            <div class="mx-auto max-w-sm space-y-3">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-neutral-200 bg-neutral-50 text-neutral-400 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-500">
                                                    <flux:icon.folder-git-2 class="size-5" />
                                                </div>

                                                <div class="space-y-1">
                                                    <div class="text-sm font-medium text-neutral-950 dark:text-neutral-50">
                                                        No hay artistas registrados todavía
                                                    </div>

                                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                        Crea el primer artista para empezar a poblar el catálogo.
                                                    </div>
                                                </div>

                                                <a
                                                    href="{{ route('artists.create') }}"
                                                    wire:navigate
                                                    class="inline-flex items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-medium text-neutral-700 transition hover:border-neutral-300 hover:bg-neutral-100 hover:text-neutral-950 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-200 dark:hover:border-neutral-600 dark:hover:bg-zinc-700 dark:hover:text-white"
                                                >
                                                        Crear artista
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <flux:modal name="confirm-artist-deletion" :show="$deletingArtistId !== null" focusable class="max-w-lg">
        <form wire:submit="deleteArtist" class="space-y-6">
            <div>
                <flux:heading size="lg">Eliminar artista</flux:heading>
                <flux:subheading>
                    {{ $deletingArtistName !== '' ? 'Vas a eliminar a ' . $deletingArtistName . ' de forma permanente.' : 'Esta acción eliminará al artista de forma permanente.' }}
                </flux:subheading>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                Esta acción no se puede deshacer.
            </div>

            <div class="flex justify-end gap-3 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled" x-on:click="$wire.cancelDelete()">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">
                    Eliminar artista
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
