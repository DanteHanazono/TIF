<?php

use App\Models\Artist;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\Component;

new class extends Component
{
    public ?Artist $artist = null;

    public string $name = '';

    public array $dayOptions = [1, 2, 3];

    public array $stageOptions = [
        'HOT STAGE',
        'HEAT GARAGE',
        'SMILE GARDEN',
        'DREAM STAGE',
        'SKY STAGE',
        'FUJI YOKO',
        'INFO CENTER',
    ];

    public array $event_days = [];

    public array $performance_rows = [];

    public function mount(?Artist $artist = null): void
    {
        $this->artist = $artist;
        $this->name = $artist?->name ?? '';

        if ($this->artist !== null) {
            $this->loadPerformanceRows();
        }
    }

    public function updated(string $name, mixed $value = null): void
    {
        if (str_starts_with($name, 'event_days')) {
            $this->syncSelectedDays();
        }
    }

    public function syncSelectedDays(): void
    {
        $currentRows = $this->performance_rows;

        $this->event_days = collect($this->event_days)
            ->map(fn ($day): int => (int) $day)
            ->filter(fn (int $day): bool => in_array($day, $this->dayOptions, true))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->performance_rows = [];

        foreach ($this->event_days as $day) {
            $this->performance_rows[$day] = $currentRows[$day] ?? [
                'stage' => '',
                'start_time' => '',
                'end_time' => '',
            ];
        }
    }

    public function save(): ?Redirector
    {
        $isCreating = $this->artist === null;
        $nameRule = Rule::unique('artists', 'name');

        if ($this->artist !== null) {
            $nameRule->ignore($this->artist);
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255', $nameRule],
        ]);

        $artist = $this->artist ?? new Artist();
        $artist->fill([
            'name' => $this->name,
        ]);
        $artist->save();

        $this->artist = $artist;

        session()->flash('status', 'Artista guardado correctamente.');

        if ($isCreating) {
            return redirect()->route('artists.index');
        }

        return null;
    }

    public function savePerformance(): void
    {
        if ($this->artist === null) {
            return;
        }

        $this->syncSelectedDays();

        $rules = [
            'event_days' => ['required', 'array', 'min:1'],
            'event_days.*' => ['integer', Rule::in($this->dayOptions)],
        ];

        foreach ($this->event_days as $day) {
            $rules["performance_rows.$day.stage"] = ['required', 'string', Rule::in($this->stageOptions)];
            $rules["performance_rows.$day.start_time"] = ['required', 'date_format:H:i'];
            $rules["performance_rows.$day.end_time"] = ['required', 'date_format:H:i', "after:performance_rows.$day.start_time"];
        }

        $this->validate($rules);

        $this->artist->performances()
            ->whereNotIn('event_day', $this->event_days)
            ->delete();

        foreach ($this->event_days as $day) {
            $this->artist->performances()->updateOrCreate([
                'event_day' => $day,
            ], [
                'event_day' => $day,
                'stage' => $this->performance_rows[$day]['stage'],
                'start_time' => Carbon::createFromFormat('H:i', $this->performance_rows[$day]['start_time'])->format('H:i:s'),
                'end_time' => Carbon::createFromFormat('H:i', $this->performance_rows[$day]['end_time'])->format('H:i:s'),
            ]);
        }

        $this->loadPerformanceRows();

        session()->flash('status', 'Presentaciones guardadas correctamente.');
    }

    private function loadPerformanceRows(): void
    {
        $performances = $this->artist
            ?->performances()
            ->orderBy('event_day')
            ->get() ?? collect();

        $this->event_days = $performances
            ->pluck('event_day')
            ->map(fn ($day): int => (int) $day)
            ->all();

        $this->performance_rows = $performances
            ->mapWithKeys(fn ($performance): array => [
                (int) $performance->event_day => [
                    'stage' => $performance->stage,
                    'start_time' => substr((string) $performance->start_time, 0, 5),
                    'end_time' => substr((string) $performance->end_time, 0, 5),
                ],
            ])
            ->all();
    }
};
?>

<section class="w-full">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if (session('status'))
            <div class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700 shadow-sm dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.95fr)]">
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-neutral-300 to-transparent opacity-70 dark:via-neutral-600"></div>

                <div class="relative space-y-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-600 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                                Artista
                            </div>

                            <flux:heading size="lg" class="text-neutral-950 dark:text-neutral-50">
                                {{ $artist ? 'Editar artista' : 'Crear artista' }}
                            </flux:heading>

                            <flux:subheading class="max-w-xl text-neutral-600 dark:text-neutral-400">
                                {{ $artist ? 'Actualiza la información principal del artista.' : 'Registra un nuevo artista con una interfaz limpia y coherente.' }}
                            </flux:subheading>
                        </div>

                        <div class="hidden h-12 w-12 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300 md:flex">
                            <flux:icon.folder-git-2 class="size-5" />
                        </div>
                    </div>

                    <form wire:submit="save" class="space-y-5">
                        <flux:input
                            wire:model="name"
                            label="Nombre del artista"
                            type="text"
                            required
                            autofocus
                            autocomplete="name"
                            class="dark:bg-zinc-950"
                        />

                        @error('name')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="flex items-center justify-end gap-3 border-t border-neutral-200 pt-5 dark:border-neutral-700">
                            <flux:button variant="primary" type="submit">
                                Guardar artista
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-zinc-900">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-neutral-300 to-transparent opacity-70 dark:via-neutral-600"></div>

                <div class="relative space-y-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-600 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                                Presentación
                            </div>

                            <flux:heading size="lg" class="text-neutral-950 dark:text-neutral-50">
                                Registro de presentación
                            </flux:heading>

                            <flux:subheading class="max-w-xl text-neutral-600 dark:text-neutral-400">
                                {{ $artist ? 'Asocia una presentación al artista actual.' : 'Primero guarda el artista para habilitar el registro de presentaciones.' }}
                            </flux:subheading>
                        </div>

                        <div class="hidden h-12 w-12 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-500 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300 md:flex">
                            <flux:icon.folder-git-2 class="size-5" />
                        </div>
                    </div>

                    @if ($artist)
                        <form wire:submit="savePerformance" class="space-y-5">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <flux:heading size="sm" class="text-neutral-950 dark:text-neutral-50">
                                        Día
                                    </flux:heading>

                                    <flux:subheading class="text-neutral-600 dark:text-neutral-400">
                                        Selecciona uno o varios días. Cada uno generará su propio registro.
                                    </flux:subheading>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    @foreach ($dayOptions as $day)
                                        <label
                                            wire:key="day-option-{{ $day }}"
                                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 transition hover:border-neutral-300 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-zinc-800 dark:hover:border-neutral-600 dark:hover:bg-zinc-700"
                                        >
                                            <input
                                                type="checkbox"
                                                wire:model.live="event_days"
                                                value="{{ $day }}"
                                                class="h-4 w-4 rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950 dark:border-neutral-600 dark:bg-zinc-900 dark:text-white dark:focus:ring-white"
                                            />

                                            <div>
                                                <div class="text-sm font-medium text-neutral-950 dark:text-neutral-50">
                                                    Día {{ $day }}
                                                </div>

                                                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                    Registro para el día {{ $day }}
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                @error('event_days')
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror

                                @error('event_days.*')
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            @if (! empty($event_days))
                                <div class="space-y-3">
                                    <div class="space-y-1">
                                        <flux:heading size="sm" class="text-neutral-950 dark:text-neutral-50">
                                            Horarios por día
                                        </flux:heading>

                                        <flux:subheading class="text-neutral-600 dark:text-neutral-400">
                                            Define un escenario y un horario para cada día elegido.
                                        </flux:subheading>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach ($event_days as $day)
                                            <div wire:key="schedule-day-{{ $day }}" class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-zinc-800">
                                                <div class="mb-4 flex items-center justify-between gap-3">
                                                    <div>
                                                        <div class="text-sm font-medium text-neutral-950 dark:text-neutral-50">
                                                            Día {{ $day }}
                                                        </div>

                                                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                                Escenario y horas independientes para este día
                                                        </div>
                                                    </div>
                                                </div>

                                                    <div class="space-y-3">
                                                        <div class="space-y-2">
                                                            <label class="block text-sm font-medium text-neutral-950 dark:text-neutral-50">
                                                                Escenario
                                                            </label>

                                                            <select
                                                                wire:model.live="performance_rows.{{ $day }}.stage"
                                                                class="w-full rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-950 shadow-sm outline-none transition focus:border-neutral-400 focus:ring-2 focus:ring-neutral-200 dark:border-neutral-700 dark:bg-zinc-950 dark:text-neutral-50 dark:focus:border-neutral-500 dark:focus:ring-neutral-700"
                                                            >
                                                                <option value="">Selecciona un escenario</option>

                                                                @foreach ($stageOptions as $option)
                                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                                @endforeach
                                                            </select>

                                                            @error("performance_rows.$day.stage")
                                                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                            @enderror
                                                        </div>

                                                        <div class="grid gap-4 sm:grid-cols-2">
                                                            <flux:input
                                                                wire:model.live="performance_rows.{{ $day }}.start_time"
                                                                label="Hora de inicio"
                                                                type="time"
                                                                required
                                                                class="dark:bg-zinc-950"
                                                            />

                                                            <flux:input
                                                                wire:model.live="performance_rows.{{ $day }}.end_time"
                                                                label="Hora de fin"
                                                                type="time"
                                                                required
                                                                class="dark:bg-zinc-950"
                                                            />
                                                        </div>
                                                    </div>

                                                    @error("performance_rows.$day.start_time")
                                                    <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror

                                                    @error("performance_rows.$day.end_time")
                                                    <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-600 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                                Cada día seleccionado guardará una presentación independiente con su propio escenario y horario.
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t border-neutral-200 pt-5 dark:border-neutral-700">
                                <flux:button variant="primary" type="submit">
                                    Guardar presentaciones
                                </flux:button>
                            </div>
                        </form>
                    @else
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-5 text-sm text-neutral-600 dark:border-neutral-700 dark:bg-zinc-800 dark:text-neutral-300">
                            Guarda primero el artista para habilitar el formulario de presentaciones.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
