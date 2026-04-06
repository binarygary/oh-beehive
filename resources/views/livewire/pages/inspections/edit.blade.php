<?php

use App\Enums\QueenStatus;
use App\Enums\VarroaMethod;
use App\Models\Inspection;
use App\Services\InspectionParserService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Inspection $inspection;

    public string $hiveId = '';
    public string $inspectedAt = '';
    public string $rawNotes = '';
    public string $weather = '';

    /** @var array<int, string> */
    public array $followupQuestions = [];
    /** @var array<string, bool> */
    public array $aiFilledFields = [];

    public string $queenSeen = '';
    public string $queenStatus = '';

    public string $eggsPresent = '';
    public string $larvaePresent = '';
    public string $cappedBroodPresent = '';
    public string $broodPatternScore = '';
    public string $framesOfBrood = '';

    public string $framesOfBees = '';
    public string $framesOfHoney = '';
    public string $honeyStoresScore = '';

    public string $varroaCount = '';
    public string $varroaMethod = '';

    public string $temperamentScore = '';
    /** @var array<int, string> */
    public array $diseaseObservations = [];
    public string $overallHealthScore = '';

    public string $feedingDone = '';
    public string $feedingNotes = '';
    public string $treatmentApplied = '';
    public string $supersAdded = '';
    public string $supersRemoved = '';

    public ?string $parseError = null;

    public function mount(Inspection $inspection): void
    {
        abort_if($inspection->user_id !== auth()->id(), 403);

        $this->inspection = $inspection;

        $nb = fn (?bool $v): string => $v === null ? '' : ($v ? '1' : '0');
        $ni = fn (?int $v): string  => $v !== null ? (string) $v : '';
        $ns = fn (?string $v): string => $v ?? '';

        $this->hiveId            = (string) $inspection->hive_id;
        $this->inspectedAt       = $inspection->inspected_at->format('Y-m-d\TH:i');
        $this->rawNotes          = $inspection->raw_notes;
        $this->weather           = $ns($inspection->weather);
        $this->queenSeen         = $nb($inspection->queen_seen);
        $this->queenStatus       = $inspection->queen_status !== null ? $inspection->queen_status->value : '';
        $this->eggsPresent       = $nb($inspection->eggs_present);
        $this->larvaePresent     = $nb($inspection->larvae_present);
        $this->cappedBroodPresent = $nb($inspection->capped_brood_present);
        $this->broodPatternScore = $ni($inspection->brood_pattern_score);
        $this->framesOfBrood     = $ni($inspection->frames_of_brood);
        $this->framesOfBees      = $ni($inspection->frames_of_bees);
        $this->framesOfHoney     = $ni($inspection->frames_of_honey);
        $this->honeyStoresScore  = $ni($inspection->honey_stores_score);
        $this->varroaCount       = $ni($inspection->varroa_count);
        $this->varroaMethod      = $inspection->varroa_method !== null ? $inspection->varroa_method->value : '';
        $this->temperamentScore  = $ni($inspection->temperament_score);
        $this->overallHealthScore = $ni($inspection->overall_health_score);
        $this->diseaseObservations = $inspection->disease_observations ?? [];
        $this->feedingDone       = $nb($inspection->feeding_done);
        $this->feedingNotes      = $ns($inspection->feeding_notes);
        $this->treatmentApplied  = $ns($inspection->treatment_applied);
        $this->supersAdded       = $ni($inspection->supers_added);
        $this->supersRemoved     = $ni($inspection->supers_removed);
        $this->followupQuestions = $inspection->followup_questions ?? [];
    }

    public function with(): array
    {
        return [
            'hives' => auth()->user()->hives()->orderBy('name')->get(),
        ];
    }

    public function parse(): void
    {
        $this->parseError = null;
        $this->aiFilledFields = [];

        if (strlen(trim($this->rawNotes)) < 5) {
            $this->parseError = 'Notes are too short to parse.';
            return;
        }

        /** @var InspectionParserService $parser */
        $parser = app(InspectionParserService::class);

        try {
            $fields = $parser->parseRaw($this->rawNotes);
        } catch (\Throwable $e) {
            $this->parseError = 'Could not parse notes. Please check the format.';
            return;
        }

        $nb = fn (mixed $v): string => $v === null ? '' : ($v ? '1' : '0');
        $ni = fn (mixed $v): string => $v !== null ? (string) (int) $v : '';
        $ns = fn (mixed $v): string => is_string($v) ? $v : '';

        if (array_key_exists('queen_seen', $fields)) {
            $this->queenSeen = $nb($fields['queen_seen']);
            $this->aiFilledFields['queenSeen'] = true;
        }

        if (array_key_exists('queen_status', $fields)) {
            $this->queenStatus = $ns($fields['queen_status']);
            $this->aiFilledFields['queenStatus'] = true;
        }

        if (array_key_exists('eggs_present', $fields)) {
            $this->eggsPresent = $nb($fields['eggs_present']);
            $this->aiFilledFields['eggsPresent'] = true;
        }

        if (array_key_exists('larvae_present', $fields)) {
            $this->larvaePresent = $nb($fields['larvae_present']);
            $this->aiFilledFields['larvaePresent'] = true;
        }

        if (array_key_exists('capped_brood_present', $fields)) {
            $this->cappedBroodPresent = $nb($fields['capped_brood_present']);
            $this->aiFilledFields['cappedBroodPresent'] = true;
        }

        if (array_key_exists('brood_pattern_score', $fields)) {
            $this->broodPatternScore = $ni($fields['brood_pattern_score']);
            $this->aiFilledFields['broodPatternScore'] = true;
        }

        if (array_key_exists('frames_of_brood', $fields)) {
            $this->framesOfBrood = $ni($fields['frames_of_brood']);
            $this->aiFilledFields['framesOfBrood'] = true;
        }

        if (array_key_exists('frames_of_bees', $fields)) {
            $this->framesOfBees = $ni($fields['frames_of_bees']);
            $this->aiFilledFields['framesOfBees'] = true;
        }

        if (array_key_exists('frames_of_honey', $fields)) {
            $this->framesOfHoney = $ni($fields['frames_of_honey']);
            $this->aiFilledFields['framesOfHoney'] = true;
        }

        if (array_key_exists('honey_stores_score', $fields)) {
            $this->honeyStoresScore = $ni($fields['honey_stores_score']);
            $this->aiFilledFields['honeyStoresScore'] = true;
        }

        if (array_key_exists('varroa_count', $fields)) {
            $this->varroaCount = $ni($fields['varroa_count']);
            $this->aiFilledFields['varroaCount'] = true;
        }

        if (array_key_exists('varroa_method', $fields)) {
            $this->varroaMethod = $ns($fields['varroa_method']);
            $this->aiFilledFields['varroaMethod'] = true;
        }

        if (array_key_exists('temperament_score', $fields)) {
            $this->temperamentScore = $ni($fields['temperament_score']);
            $this->aiFilledFields['temperamentScore'] = true;
        }

        if (array_key_exists('overall_health_score', $fields)) {
            $this->overallHealthScore = $ni($fields['overall_health_score']);
            $this->aiFilledFields['overallHealthScore'] = true;
        }

        if (array_key_exists('feeding_done', $fields)) {
            $this->feedingDone = $nb($fields['feeding_done']);
            $this->aiFilledFields['feedingDone'] = true;
        }

        if (array_key_exists('feeding_notes', $fields)) {
            $this->feedingNotes = $ns($fields['feeding_notes']);
            $this->aiFilledFields['feedingNotes'] = true;
        }

        if (array_key_exists('treatment_applied', $fields)) {
            $this->treatmentApplied = $ns($fields['treatment_applied']);
            $this->aiFilledFields['treatmentApplied'] = true;
        }

        if (array_key_exists('supers_added', $fields)) {
            $this->supersAdded = $ni($fields['supers_added']);
            $this->aiFilledFields['supersAdded'] = true;
        }

        if (array_key_exists('supers_removed', $fields)) {
            $this->supersRemoved = $ni($fields['supers_removed']);
            $this->aiFilledFields['supersRemoved'] = true;
        }

        if (array_key_exists('weather', $fields)) {
            $this->weather = $ns($fields['weather']);
            $this->aiFilledFields['weather'] = true;
        }

        if (array_key_exists('disease_observations', $fields) && is_array($fields['disease_observations'])) {
            $this->diseaseObservations = $fields['disease_observations'];
            $this->aiFilledFields['diseaseObservations'] = true;
        }

        $fq = $fields['followup_questions'] ?? null;
        $this->followupQuestions = is_array($fq) ? $fq : [];
    }

    public function updated(string $property): void
    {
        unset($this->aiFilledFields[$property]);
    }

    public function updatedDiseaseObservations(mixed $value, mixed $key): void
    {
        unset($this->aiFilledFields['diseaseObservations']);
    }

    public function save(): void
    {
        $this->validate([
            'hiveId'             => ['required', 'integer', Rule::exists('hives', 'id')->where('user_id', auth()->id())],
            'inspectedAt'        => ['required', 'date'],
            'rawNotes'           => ['required', 'string'],
            'queenStatus'        => ['nullable', Rule::enum(QueenStatus::class)],
            'varroaMethod'       => ['nullable', Rule::enum(VarroaMethod::class)],
            'broodPatternScore'  => ['nullable', 'integer', 'min:1', 'max:5'],
            'honeyStoresScore'   => ['nullable', 'integer', 'min:1', 'max:5'],
            'temperamentScore'   => ['nullable', 'integer', 'min:1', 'max:5'],
            'overallHealthScore' => ['nullable', 'integer', 'min:1', 'max:5'],
            'framesOfBrood'      => ['nullable', 'integer', 'min:0'],
            'framesOfBees'       => ['nullable', 'integer', 'min:0'],
            'framesOfHoney'      => ['nullable', 'integer', 'min:0'],
            'varroaCount'        => ['nullable', 'integer', 'min:0'],
            'supersAdded'        => ['nullable', 'integer', 'min:0'],
            'supersRemoved'      => ['nullable', 'integer', 'min:0'],
            'diseaseObservations'   => ['nullable', 'array'],
            'diseaseObservations.*' => ['string'],
        ]);

        $nb = fn (string $v): ?bool => $v === '' ? null : (bool) $v;
        $ni = fn (string $v): ?int  => $v !== '' ? (int) $v : null;
        $ns = fn (string $v): ?string => $v !== '' ? $v : null;

        $this->inspection->update([
            'hive_id'              => (int) $this->hiveId,
            'inspected_at'         => $this->inspectedAt,
            'raw_notes'            => $this->rawNotes,
            'weather'              => $ns($this->weather),
            'queen_seen'           => $nb($this->queenSeen),
            'queen_status'         => $ns($this->queenStatus),
            'eggs_present'         => $nb($this->eggsPresent),
            'larvae_present'       => $nb($this->larvaePresent),
            'capped_brood_present' => $nb($this->cappedBroodPresent),
            'brood_pattern_score'  => $ni($this->broodPatternScore),
            'frames_of_brood'      => $ni($this->framesOfBrood),
            'frames_of_bees'       => $ni($this->framesOfBees),
            'frames_of_honey'      => $ni($this->framesOfHoney),
            'honey_stores_score'   => $ni($this->honeyStoresScore),
            'varroa_count'         => $ni($this->varroaCount),
            'varroa_method'        => $ns($this->varroaMethod),
            'temperament_score'    => $ni($this->temperamentScore),
            'overall_health_score' => $ni($this->overallHealthScore),
            'disease_observations' => $this->diseaseObservations ?: null,
            'feeding_done'         => $nb($this->feedingDone),
            'feeding_notes'        => $ns($this->feedingNotes),
            'treatment_applied'    => $ns($this->treatmentApplied),
            'supers_added'         => $ni($this->supersAdded),
            'supers_removed'       => $ni($this->supersRemoved),
            'followup_questions'   => $this->followupQuestions ?: null,
        ]);

        $this->redirect(route('inspections.index'), navigate: true);
    }
}; ?>

<div class="max-w-2xl space-y-6">

    <div>
        <a href="{{ route('inspections.index') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm text-base-content/50 hover:text-base-content transition-colors mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Inspections
        </a>
        <h1 class="text-2xl font-bold text-base-content">Edit Inspection</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $inspection->hive->name }} · {{ $inspection->inspected_at->format('M j, Y') }}</p>
    </div>

    <form wire:submit="save" class="space-y-4">

        {{-- Observations textarea — primary input, at the top --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-5 space-y-3">
                <div class="flex items-center justify-between min-h-[1.5rem]">
                    <label class="text-sm font-medium text-base-content">
                        Observations <span class="text-error">*</span>
                    </label>
                    <button type="button" wire:click="parse" class="btn btn-sm btn-outline btn-primary">
                        <span wire:loading.remove wire:target="parse">Parse</span>
                        <span wire:loading wire:target="parse" class="loading loading-spinner loading-xs"></span>
                    </button>
                </div>
                <textarea wire:model="rawNotes" rows="7"
                    class="textarea textarea-bordered w-full resize-y text-sm leading-relaxed"></textarea>
                @error('rawNotes') <p class="text-error text-xs">{{ $message }}</p> @enderror
            </div>
        </div>

        @if($parseError)
            <div class="alert alert-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>{{ $parseError }}</span>
            </div>
        @endif

        {{-- Follow-up questions --}}
        @if(!empty($followupQuestions))
        <div class="alert alert-info">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-semibold text-sm">Couldn't determine everything — add details to your notes to fill these in:</p>
                <ul class="list-disc list-inside text-sm mt-1 space-y-0.5">
                    @foreach($followupQuestions as $q)
                        <li>{{ $q }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- Fields — dimmed while AI is running --}}
        <div class="space-y-4">

            {{-- Basics --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide">Basics</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5 col-span-2 sm:col-span-1">
                            <label class="text-sm font-medium text-base-content">Hive <span class="text-error">*</span></label>
                            <select wire:model="hiveId" class="select select-bordered w-full">
                                <option value="">Select a hive…</option>
                                @foreach($hives as $hive)
                                    <option value="{{ $hive->id }}">{{ $hive->name }}</option>
                                @endforeach
                            </select>
                            @error('hiveId') <p class="text-error text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5 col-span-2 sm:col-span-1">
                            <label class="text-sm font-medium text-base-content">Date & Time <span class="text-error">*</span></label>
                            <input type="datetime-local" wire:model="inspectedAt" class="input input-bordered w-full" />
                            @error('inspectedAt') <p class="text-error text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-base-content">Weather</label>
                        <input type="text" wire:model.blur="weather" class="input input-bordered w-full" placeholder="e.g. Sunny, 18°C, light breeze" />
                    </div>
                </div>
            </div>

            {{-- Queen & Brood --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide">Queen & Brood</h2>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                        @foreach([['queenSeen','Queen Seen?'],['eggsPresent','Eggs?'],['larvaePresent','Larvae?'],['cappedBroodPresent','Capped Brood?']] as [$prop, $label])
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">{{ $label }}</label>
                            <div class="join">
                                <button type="button" wire:click="$set('{{ $prop }}', '')"
                                    @class(['join-item btn btn-sm', 'btn-neutral' => $this->$prop === '', 'btn-ghost' => $this->$prop !== ''])>—</button>
                                <button type="button" wire:click="$set('{{ $prop }}', '1')"
                                    @class(['join-item btn btn-sm', 'btn-success' => $this->$prop === '1', 'btn-ghost' => $this->$prop !== '1'])>Yes</button>
                                <button type="button" wire:click="$set('{{ $prop }}', '0')"
                                    @class(['join-item btn btn-sm', 'btn-error' => $this->$prop === '0', 'btn-ghost' => $this->$prop !== '0'])>No</button>
                            </div>
                        </div>
                        @endforeach
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Queen Status</label>
                            <select wire:model.change="queenStatus" class="select select-bordered w-full select-sm">
                                <option value="">Unknown</option>
                                @foreach(QueenStatus::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Brood Pattern <span class="text-xs text-base-content/40">(1–5)</span></label>
                            <div class="join">
                                <button type="button" wire:click="$set('broodPatternScore', '')"
                                    @class(['join-item btn btn-sm', 'btn-neutral' => $broodPatternScore === '', 'btn-ghost' => $broodPatternScore !== ''])>—</button>
                                @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('broodPatternScore', '{{ $i }}')"
                                    @class(['join-item btn btn-sm', 'btn-primary' => $broodPatternScore === (string)$i, 'btn-ghost' => $broodPatternScore !== (string)$i])>{{ $i }}</button>
                                @endfor
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Frames of Brood</label>
                            <input type="number" wire:model.blur="framesOfBrood" min="0" max="20" class="input input-bordered input-sm w-28" placeholder="—" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Population & Stores --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide">Population & Stores</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Frames of Bees</label>
                            <input type="number" wire:model.blur="framesOfBees" min="0" max="20" class="input input-bordered input-sm w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Frames of Honey</label>
                            <input type="number" wire:model.blur="framesOfHoney" min="0" max="20" class="input input-bordered input-sm w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Honey Stores <span class="text-xs text-base-content/40">(1–5)</span></label>
                            <div class="join">
                                <button type="button" wire:click="$set('honeyStoresScore', '')"
                                    @class(['join-item btn btn-xs', 'btn-neutral' => $honeyStoresScore === '', 'btn-ghost' => $honeyStoresScore !== ''])>—</button>
                                @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('honeyStoresScore', '{{ $i }}')"
                                    @class(['join-item btn btn-xs', 'btn-primary' => $honeyStoresScore === (string)$i, 'btn-ghost' => $honeyStoresScore !== (string)$i])>{{ $i }}</button>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Behaviour & Health --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide">Behaviour & Health</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Temperament <span class="text-xs text-base-content/40">1=calm 5=defensive</span></label>
                            <div class="join">
                                <button type="button" wire:click="$set('temperamentScore', '')"
                                    @class(['join-item btn btn-sm', 'btn-neutral' => $temperamentScore === '', 'btn-ghost' => $temperamentScore !== ''])>—</button>
                                @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('temperamentScore', '{{ $i }}')"
                                    @class(['join-item btn btn-sm', 'btn-primary' => $temperamentScore === (string)$i, 'btn-ghost' => $temperamentScore !== (string)$i])>{{ $i }}</button>
                                @endfor
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Overall Health <span class="text-xs text-base-content/40">(1–5)</span></label>
                            <div class="join">
                                <button type="button" wire:click="$set('overallHealthScore', '')"
                                    @class(['join-item btn btn-sm', 'btn-neutral' => $overallHealthScore === '', 'btn-ghost' => $overallHealthScore !== ''])>—</button>
                                @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('overallHealthScore', '{{ $i }}')"
                                    @class(['join-item btn btn-sm', 'btn-primary' => $overallHealthScore === (string)$i, 'btn-ghost' => $overallHealthScore !== (string)$i])>{{ $i }}</button>
                                @endfor
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-base-content">Disease Observations</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach(['Chalkbrood','Sacbrood','EFB','AFB','Nosema','Small Hive Beetle','Wax Moth','Deformed Wing Virus'] as $disease)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.change="diseaseObservations" value="{{ $disease }}" class="checkbox checkbox-sm checkbox-primary" />
                                <span class="text-sm">{{ $disease }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Varroa --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide">Varroa <span class="text-xs font-normal normal-case">(optional)</span></h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Mite Count <span class="text-xs text-base-content/40">per 100 bees</span></label>
                            <input type="number" wire:model.blur="varroaCount" min="0" class="input input-bordered input-sm w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Method</label>
                            <select wire:model.change="varroaMethod" class="select select-bordered select-sm w-full">
                                <option value="">—</option>
                                @foreach(VarroaMethod::cases() as $m)
                                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions Taken --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h2 class="font-semibold text-sm text-base-content/60 uppercase tracking-wide">Actions Taken</h2>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Feeding Done?</label>
                            <div class="join">
                                <button type="button" wire:click="$set('feedingDone', '')"
                                    @class(['join-item btn btn-sm', 'btn-neutral' => $feedingDone === '', 'btn-ghost' => $feedingDone !== ''])>—</button>
                                <button type="button" wire:click="$set('feedingDone', '1')"
                                    @class(['join-item btn btn-sm', 'btn-success' => $feedingDone === '1', 'btn-ghost' => $feedingDone !== '1'])>Yes</button>
                                <button type="button" wire:click="$set('feedingDone', '0')"
                                    @class(['join-item btn btn-sm', 'btn-error' => $feedingDone === '0', 'btn-ghost' => $feedingDone !== '0'])>No</button>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Feeding Notes</label>
                            <input type="text" wire:model.blur="feedingNotes" class="input input-bordered input-sm w-full" placeholder="e.g. 1:1 syrup, 1L" />
                        </div>
                        <div class="space-y-1.5 col-span-2">
                            <label class="text-sm font-medium text-base-content">Treatment Applied</label>
                            <textarea wire:model.blur="treatmentApplied" rows="2" class="textarea textarea-bordered w-full resize-none"></textarea>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Supers Added</label>
                            <input type="number" wire:model.blur="supersAdded" min="0" class="input input-bordered input-sm w-24" placeholder="0" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Supers Removed</label>
                            <input type="number" wire:model.blur="supersRemoved" min="0" class="input input-bordered input-sm w-24" placeholder="0" />
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end fields wrapper --}}

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pb-4">
            <a href="{{ route('inspections.index') }}" wire:navigate class="btn btn-ghost btn-sm">Cancel</a>
            <button type="submit" class="btn btn-primary btn-sm">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>

    </form>
</div>
