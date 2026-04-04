<?php

use App\Enums\QueenStatus;
use App\Enums\VarroaMethod;
use App\Services\InspectionParserService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $hiveId = '';
    public string $inspectedAt = '';
    public string $rawNotes = '';
    public string $weather = '';

    /** @var array<int, string> */
    public array $followupQuestions = [];

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

    public function mount(): void
    {
        $this->inspectedAt = now()->format('Y-m-d\TH:i');

        $hiveId = request()->query('hive');
        if ($hiveId) {
            $hive = auth()->user()->hives()->find((int) $hiveId);
            if ($hive) {
                $this->hiveId = (string) $hive->id;
            }
        }
    }

    public function with(): array
    {
        return [
            'hives' => auth()->user()->hives()->orderBy('name')->get(),
        ];
    }

    public function updatedRawNotes(): void
    {
        if (strlen(trim($this->rawNotes)) < 15) {
            return;
        }

        /** @var InspectionParserService $parser */
        $parser = app(InspectionParserService::class);

        try {
            $fields = $parser->parseRaw($this->rawNotes);
        } catch (\Throwable) {
            return;
        }

        $nb = fn (mixed $v): string => $v === null ? '' : ($v ? '1' : '0');
        $ni = fn (mixed $v): string => $v !== null ? (string) (int) $v : '';
        $ns = fn (mixed $v): string => is_string($v) ? $v : '';

        if (array_key_exists('queen_seen', $fields))          $this->queenSeen          = $nb($fields['queen_seen']);
        if (array_key_exists('queen_status', $fields))        $this->queenStatus        = $ns($fields['queen_status']);
        if (array_key_exists('eggs_present', $fields))        $this->eggsPresent        = $nb($fields['eggs_present']);
        if (array_key_exists('larvae_present', $fields))      $this->larvaePresent      = $nb($fields['larvae_present']);
        if (array_key_exists('capped_brood_present', $fields)) $this->cappedBroodPresent = $nb($fields['capped_brood_present']);
        if (array_key_exists('brood_pattern_score', $fields)) $this->broodPatternScore  = $ni($fields['brood_pattern_score']);
        if (array_key_exists('frames_of_brood', $fields))     $this->framesOfBrood      = $ni($fields['frames_of_brood']);
        if (array_key_exists('frames_of_bees', $fields))      $this->framesOfBees       = $ni($fields['frames_of_bees']);
        if (array_key_exists('frames_of_honey', $fields))     $this->framesOfHoney      = $ni($fields['frames_of_honey']);
        if (array_key_exists('honey_stores_score', $fields))  $this->honeyStoresScore   = $ni($fields['honey_stores_score']);
        if (array_key_exists('varroa_count', $fields))        $this->varroaCount        = $ni($fields['varroa_count']);
        if (array_key_exists('varroa_method', $fields))       $this->varroaMethod       = $ns($fields['varroa_method']);
        if (array_key_exists('temperament_score', $fields))   $this->temperamentScore   = $ni($fields['temperament_score']);
        if (array_key_exists('overall_health_score', $fields)) $this->overallHealthScore = $ni($fields['overall_health_score']);
        if (array_key_exists('feeding_done', $fields))        $this->feedingDone        = $nb($fields['feeding_done']);
        if (array_key_exists('feeding_notes', $fields))       $this->feedingNotes       = $ns($fields['feeding_notes']);
        if (array_key_exists('treatment_applied', $fields))   $this->treatmentApplied   = $ns($fields['treatment_applied']);
        if (array_key_exists('supers_added', $fields))        $this->supersAdded        = $ni($fields['supers_added']);
        if (array_key_exists('supers_removed', $fields))      $this->supersRemoved      = $ni($fields['supers_removed']);
        if (array_key_exists('weather', $fields))             $this->weather            = $ns($fields['weather']);

        if (array_key_exists('disease_observations', $fields) && is_array($fields['disease_observations'])) {
            $this->diseaseObservations = $fields['disease_observations'];
        }

        $fq = $fields['followup_questions'] ?? null;
        $this->followupQuestions = is_array($fq) ? $fq : [];
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

        $inspection = auth()->user()->inspections()->create([
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

        $this->redirect(route('inspections.edit', $inspection), navigate: true);
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
        <h1 class="text-2xl font-bold text-base-content">Record Inspection</h1>
    </div>

    <form wire:submit="save" class="space-y-4">

        {{-- Observations textarea — primary input, at the top --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body p-5 space-y-3">
                <div class="flex items-center justify-between min-h-[1.5rem]">
                    <label class="text-sm font-medium text-base-content">
                        Observations <span class="text-error">*</span>
                    </label>
                    <span wire:loading wire:target="updatedRawNotes"
                          class="flex items-center gap-1.5 text-xs text-base-content/50">
                        <span class="loading loading-spinner loading-xs"></span>
                        Analyzing…
                    </span>
                </div>
                <textarea wire:model.live.debounce.5000ms="rawNotes" rows="7"
                    class="textarea textarea-bordered w-full resize-y text-sm leading-relaxed"
                    placeholder="Write your raw notes here — queen seen on frame 3, good brood pattern, 6 frames of bees, added a super…"></textarea>
                @error('rawNotes') <p class="text-error text-xs">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Follow-up questions — shown when AI couldn't determine everything --}}
        @if(!empty($followupQuestions))
        <div class="alert alert-warning">
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
        <div wire:loading.class="opacity-40 pointer-events-none" wire:target="updatedRawNotes" class="space-y-4">

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
                        <input type="text" wire:model="weather" class="input input-bordered w-full" placeholder="e.g. Sunny, 18°C, light breeze" />
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
                            <select wire:model="queenStatus" class="select select-bordered w-full select-sm">
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
                            <input type="number" wire:model="framesOfBrood" min="0" max="20" class="input input-bordered input-sm w-28" placeholder="—" />
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
                            <input type="number" wire:model="framesOfBees" min="0" max="20" class="input input-bordered input-sm w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Frames of Honey</label>
                            <input type="number" wire:model="framesOfHoney" min="0" max="20" class="input input-bordered input-sm w-full" placeholder="—" />
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
                        <p class="text-xs text-base-content/40">Check all that apply</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach(['Chalkbrood','Sacbrood','EFB','AFB','Nosema','Small Hive Beetle','Wax Moth','Deformed Wing Virus'] as $disease)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="diseaseObservations" value="{{ $disease }}" class="checkbox checkbox-sm checkbox-primary" />
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
                            <input type="number" wire:model="varroaCount" min="0" class="input input-bordered input-sm w-full" placeholder="—" />
                            @error('varroaCount') <p class="text-error text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Method</label>
                            <select wire:model="varroaMethod" class="select select-bordered select-sm w-full">
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
                            <input type="text" wire:model="feedingNotes" class="input input-bordered input-sm w-full" placeholder="e.g. 1:1 syrup, 1L" />
                        </div>
                        <div class="space-y-1.5 col-span-2">
                            <label class="text-sm font-medium text-base-content">Treatment Applied</label>
                            <textarea wire:model="treatmentApplied" rows="2" class="textarea textarea-bordered w-full resize-none"
                                placeholder="e.g. Oxalic acid vaporisation"></textarea>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Supers Added</label>
                            <input type="number" wire:model="supersAdded" min="0" class="input input-bordered input-sm w-24" placeholder="0" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-base-content">Supers Removed</label>
                            <input type="number" wire:model="supersRemoved" min="0" class="input input-bordered input-sm w-24" placeholder="0" />
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end fields wrapper --}}

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pb-4">
            <a href="{{ route('inspections.index') }}" wire:navigate class="btn btn-ghost btn-sm">Cancel</a>
            <button type="submit" class="btn btn-primary btn-sm">
                <span wire:loading.remove wire:target="save">Save Inspection</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>

    </form>
</div>
