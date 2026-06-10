<?php

use App\Models\Performance;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component
{
    public int $activeDay = 1;

    /**
     * @return array<int, int>
     */
    public function dayOptions(): array
    {
        return [1, 2, 3];
    }

    public function setActiveDay(int $day): void
    {
        if (! in_array($day, $this->dayOptions(), true)) {
            return;
        }

        $this->activeDay = $day;
    }

    public function render()
    {
        return $this->view([
            'dayOptions' => $this->dayOptions(),
            'performances' => $this->performancesForActiveDay(),
            'performanceCounts' => $this->performanceCounts(),
        ]);
    }

    /**
     * @return Collection<int, Performance>
     */
    private function performancesForActiveDay(): Collection
    {
        return Performance::query()
            ->with('artist:id,name')
            ->where('event_day', $this->activeDay)
            ->orderBy('start_time')
            ->orderBy('stage')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function performanceCounts(): array
    {
        $counts = Performance::query()
            ->selectRaw('event_day, count(*) as aggregate')
            ->whereIn('event_day', $this->dayOptions())
            ->groupBy('event_day')
            ->pluck('aggregate', 'event_day')
            ->map(fn ($count): int => (int) $count)
            ->all();

        return collect($this->dayOptions())
            ->mapWithKeys(fn (int $day): array => [$day => $counts[$day] ?? 0])
            ->all();
    }
};
?>

<main class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_20%_10%,rgba(250,204,21,0.18),transparent_28%),radial-gradient(circle_at_88%_18%,rgba(20,184,166,0.16),transparent_30%),linear-gradient(135deg,#080808_0%,#171717_48%,#111111_100%)]">
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex items-center justify-between border-b border-white/10 pb-5">
            <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                <span class="flex size-10 items-center justify-center rounded-lg border border-yellow-300/30 bg-yellow-300 text-sm font-black text-neutral-950 shadow-[0_0_36px_rgba(250,204,21,0.28)]">
                    T
                </span>
                <span class="text-sm font-semibold uppercase tracking-[0.32em] text-neutral-300">
                    Timetable
                </span>
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-neutral-200 transition hover:border-yellow-300/50 hover:bg-yellow-300 hover:text-neutral-950" wire:navigate>
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-neutral-200 transition hover:border-yellow-300/50 hover:bg-yellow-300 hover:text-neutral-950" wire:navigate>
                    Entrar
                </a>
            @endauth
        </header>

        <section class="grid flex-1 gap-8 py-8 lg:grid-cols-[0.8fr_1.4fr] lg:items-start lg:py-12">
            <div class="lg:sticky lg:top-8">
                <p class="mb-5 text-xs font-semibold uppercase tracking-[0.36em] text-yellow-300">
                    Live schedule
                </p>

                <h1 class="max-w-xl text-4xl font-black leading-[0.95] tracking-normal text-white sm:text-6xl">
                    Escenarios, artistas y horarios en una sola vista.
                </h1>

                <p class="mt-5 max-w-md text-base leading-7 text-neutral-300">
                    Consulta cada día del evento con las presentaciones ordenadas por hora de inicio.
                </p>

                <div class="mt-8 grid grid-cols-3 overflow-hidden rounded-xl border border-white/10 bg-neutral-950/60 p-1 shadow-2xl shadow-black/30 backdrop-blur">
                    @foreach ($dayOptions as $day)
                        <button
                            type="button"
                            wire:key="home-day-tab-{{ $day }}"
                            wire:click="setActiveDay({{ $day }})"
                            class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-lg px-3 text-center text-sm font-bold transition {{ $activeDay === $day ? 'bg-yellow-300 text-neutral-950 shadow-[0_10px_30px_rgba(250,204,21,0.24)]' : 'text-neutral-300 hover:bg-white/10 hover:text-white' }}"
                            aria-pressed="{{ $activeDay === $day ? 'true' : 'false' }}"
                        >
                            <span>Día {{ $day }}</span>
                            <span class="text-xs font-medium opacity-75">{{ $performanceCounts[$day] }} shows</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-neutral-950/72 shadow-2xl shadow-black/35 backdrop-blur">
                <div class="flex flex-col gap-3 border-b border-white/10 p-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-teal-300">
                            Día {{ $activeDay }}
                        </p>
                        <h2 class="mt-2 text-2xl font-black text-white">
                            Timetable
                        </h2>
                    </div>

                    <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-sm text-neutral-300">
                        {{ $performances->count() }} presentaciones
                    </div>
                </div>

                <div class="divide-y divide-white/10">
                    @forelse ($performances as $performance)
                        <article wire:key="home-performance-{{ $performance->id }}" class="grid gap-4 p-5 transition hover:bg-white/[0.04] sm:grid-cols-[120px_1fr] sm:items-center">
                            <div class="font-mono text-2xl font-black text-yellow-300">
                                {{ str($performance->start_time)->substr(0, 5) }}
                            </div>

                            <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                                <div>
                                    <h3 class="text-xl font-black text-white">
                                        {{ $performance->artist->name }}
                                    </h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-neutral-300">
                                        <span class="rounded-full border border-teal-300/30 bg-teal-300/10 px-3 py-1 font-semibold text-teal-200">
                                            {{ $performance->stage }}
                                        </span>
                                        <span>{{ str($performance->start_time)->substr(0, 5) }} - {{ str($performance->end_time)->substr(0, 5) }}</span>
                                    </div>
                                </div>

                                <div class="h-2 rounded-full bg-gradient-to-r from-yellow-300 via-teal-300 to-red-400 sm:h-12 sm:w-2"></div>
                            </div>
                        </article>
                    @empty
                        <div class="p-10 text-center">
                            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-xl font-black text-yellow-300">
                                {{ $activeDay }}
                            </div>
                            <h3 class="text-lg font-bold text-white">
                                Sin presentaciones para este día
                            </h3>
                            <p class="mt-2 text-sm text-neutral-400">
                                Cuando registres artistas para el día {{ $activeDay }}, aparecerán aquí ordenados por hora.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</main>
