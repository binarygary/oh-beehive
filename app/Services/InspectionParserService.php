<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InspectionParserInterface;
use App\Models\Inspection;
use OpenAI\Client;

class InspectionParserService implements InspectionParserInterface
{
    public function __construct(private readonly Client $client) {}

    /**
     * Parse raw notes text and return extracted field values.
     *
     * @return array<string, mixed>
     */
    public function parseRaw(string $rawNotes): array
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $rawNotes],
            ],
        ]);

        $content = $response->choices[0]->message->content;

        if ($content === null) {
            return [];
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($content, true);

        if (! is_array($data)) {
            return [];
        }

        return $this->extractFields($data);
    }

    /**
     * Parse raw notes from an inspection and persist the extracted fields.
     */
    public function parse(Inspection $inspection): void
    {
        $fields = $this->parseRaw($inspection->raw_notes);

        if ($fields !== []) {
            $inspection->update($fields);
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a beekeeping inspection parser. Extract structured data from a beekeeper's raw inspection notes and return a JSON object.

Use null for any field that is not mentioned or cannot be determined. Only add follow-up questions for things that are genuinely ambiguous or need clarification.

Fields to extract:
- queen_seen: boolean|null
- queen_status: "laying"|"not_laying"|"swarm_cells"|"supersedure_cells"|null
- eggs_present: boolean|null
- larvae_present: boolean|null
- capped_brood_present: boolean|null
- brood_pattern_score: integer 1-5|null (1=poor/patchy, 5=solid/compact)
- frames_of_brood: integer|null
- frames_of_bees: integer|null
- frames_of_honey: integer|null
- honey_stores_score: integer 1-5|null (1=almost empty, 5=abundant)
- varroa_count: integer|null (mites per 100 bees)
- varroa_method: "sugar_roll"|"alcohol_wash"|"sticky_board"|null
- temperament_score: integer 1-5|null (1=calm, 5=very defensive)
- disease_observations: array of strings (only from: Chalkbrood, Sacbrood, EFB, AFB, Nosema, Small Hive Beetle, Wax Moth, Deformed Wing Virus)|null
- overall_health_score: integer 1-5|null (1=very poor, 5=excellent)
- feeding_done: boolean|null
- feeding_notes: string|null
- treatment_applied: string|null
- supers_added: integer|null
- supers_removed: integer|null
- weather: string|null
- followup_questions: array of strings (questions for the keeper about unclear or missing details — empty array if nothing to ask)
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractFields(array $data): array
    {
        $fields = [];

        $validDiseases = ['Chalkbrood', 'Sacbrood', 'EFB', 'AFB', 'Nosema', 'Small Hive Beetle', 'Wax Moth', 'Deformed Wing Virus'];

        foreach (['queen_seen', 'eggs_present', 'larvae_present', 'capped_brood_present', 'feeding_done'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $fields[$field] = (bool) $data[$field];
            }
        }

        foreach (['brood_pattern_score', 'frames_of_brood', 'frames_of_bees', 'frames_of_honey',
            'honey_stores_score', 'varroa_count', 'temperament_score', 'overall_health_score',
            'supers_added', 'supers_removed'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $fields[$field] = (int) $data[$field];
            }
        }

        foreach (['queen_status', 'varroa_method', 'treatment_applied', 'feeding_notes', 'weather'] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field]) && $data[$field] !== '') {
                $fields[$field] = $data[$field];
            }
        }

        if (isset($data['disease_observations']) && is_array($data['disease_observations'])) {
            $filtered = array_values(array_intersect($data['disease_observations'], $validDiseases));
            if ($filtered !== []) {
                $fields['disease_observations'] = $filtered;
            }
        }

        $followup = isset($data['followup_questions']) && is_array($data['followup_questions'])
            ? array_values(array_filter($data['followup_questions'], fn ($q) => is_string($q) && $q !== ''))
            : [];

        $fields['followup_questions'] = $followup !== [] ? $followup : null;

        return $fields;
    }
}
