<?php

namespace BIWEB\LaravelOpenapi\Commands;

use BIWEB\LaravelOpenapi\Exceptions\InvalidOpenapiDefinition;
use BIWEB\LaravelOpenapi\LaravelOpenapi;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class GenerateCommand extends Command
{
    public $signature = 'openapi:generate
        {document? : The document to generate, the default one otherwise}
        {--all : Generate every configured document}
        {--output= : The file to write, the document output otherwise}
        {--format= : json or yaml, deduced from the file extension otherwise}';

    public $description = 'Generate the OpenAPI document';

    public function handle(LaravelOpenapi $openapi, Filesystem $files): int
    {
        try {
            if ($this->option('all') && ($this->argument('document') !== null || $this->option('output') !== null)) {
                throw new InvalidOpenapiDefinition('The --all option cannot be combined with a document or --output.');
            }

            $documents = $this->option('all') ? array_keys(config('openapi.documents', [])) : [$this->argument('document') ?? config('openapi.default')];

            foreach ($documents as $document) {
                $document = (string) $document;
                // generated first, so an unknown document is reported as such rather than as a missing output
                $generated = $openapi->generate($document);
                $path = $this->option('output') ?? config("openapi.documents.{$document}.output")
                    ?? throw new InvalidOpenapiDefinition("Document [{$document}] has no output file, set its output or pass --output.");

                $files->ensureDirectoryExists(dirname($path));
                $files->put($path, $this->encode($generated, $path));
                $this->components->info("Document [{$document}] written to [{$path}].");
            }
        } catch (InvalidOpenapiDefinition $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $document */
    private function encode(array $document, string $path): string
    {
        $format = $this->option('format') ?? (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['yaml', 'yml'], true) ? 'yaml' : 'json');

        return match ($format) {
            'json' => json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n",
            // empty maps are stdClass, so an empty array is a list
            'yaml' => Yaml::dump($document, PHP_INT_MAX, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE),
            default => throw new InvalidOpenapiDefinition("Unknown format [{$format}], expected json or yaml."),
        };
    }
}
