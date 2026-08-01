<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;

final class ImportCatalogDryRunCommand extends Command
{
    protected $signature = 'catalog:import-dry-run {path : JSON file path to validate}';

    protected $description = 'Validate a catalog import JSON file without writing database changes.';

    /**
     * @var list<string>
     */
    private const REQUIRED_SECTIONS = [
        'categories',
        'products',
    ];

    /**
     * @var list<string>
     */
    private const OPTIONAL_SECTIONS = [
        'product_outlet_availabilities',
        'variants',
        'variant_outlet_availabilities',
        'modifier_groups',
        'modifier_options',
        'modifier_option_outlet_overrides',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! File::isFile($path)) {
            $this->error('Import file was not found.');

            return self::FAILURE;
        }

        try {
            $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('Import file is not valid JSON: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! is_array($payload)) {
            $this->error('Import payload must be a JSON object.');

            return self::FAILURE;
        }

        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Catalog import dry-run passed. No database changes were written.');

        return self::SUCCESS;
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<string>
     */
    private function validatePayload(array $payload): array
    {
        $errors = [];

        foreach (self::REQUIRED_SECTIONS as $section) {
            if (! array_key_exists($section, $payload) || ! is_array($payload[$section])) {
                $errors[] = sprintf('Section "%s" is required and must be an array.', $section);
            }
        }

        foreach (self::OPTIONAL_SECTIONS as $section) {
            if (array_key_exists($section, $payload) && ! is_array($payload[$section])) {
                $errors[] = sprintf('Section "%s" must be an array when present.', $section);
            }
        }

        foreach (['categories', 'products', 'variants', 'modifier_groups', 'modifier_options'] as $section) {
            if (! isset($payload[$section]) || ! is_array($payload[$section])) {
                continue;
            }

            foreach ($payload[$section] as $index => $row) {
                if (! is_array($row)) {
                    $errors[] = sprintf('%s[%s] must be an object.', $section, (string) $index);
                }
            }
        }

        return $errors;
    }
}
