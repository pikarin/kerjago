<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedTalentData extends Command
{
    protected $signature = 'talent:seed
        {--tools-path= : Override the seedgen directory (defaults to tools/seedgen)}';

    protected $description = 'Destroy and re-seed the 13k demo jobseekers in Postgres and Typesense (~16s, no re-embedding)';

    /**
     * Thin wrapper around the pre-built seedgen binary. The heavy lifting —
     * COPY-loading the CSV artifacts and restoring the Typesense snapshot with
     * its embedding vectors intact — lives in tools/seedgen. Only rows whose
     * user email ends in @mail.test are deleted, so hand-made accounts survive.
     *
     * Connection settings are forwarded from this app's config, so whatever
     * .env points at is what gets seeded.
     */
    public function handle(): int
    {
        $dir = $this->option('tools-path') ?: base_path('tools/seedgen');
        $binary = $dir.'/seedgen';
        $data = $dir.'/data';

        if (! is_executable($binary) || ! is_file($data.'/typesense-snapshot.jsonl.gz')) {
            $this->error("seedgen binary or data artifacts missing under {$dir}.");
            $this->line('Expected: seedgen (executable) and data/*.gz including typesense-snapshot.jsonl.gz.');

            return self::FAILURE;
        }

        $db = config('database.connections.'.config('database.default'));
        $dsn = sprintf(
            'postgres://%s%s@%s:%s/%s',
            $db['username'],
            $db['password'] !== '' && $db['password'] !== null ? ':'.$db['password'] : '',
            $db['host'],
            $db['port'],
            $db['database'],
        );

        $typesense = config('scout.typesense.client-settings');
        $node = $typesense['nodes'][0];

        $process = new Process([
            $binary, 'reset',
            '--in', $data,
            '--dsn', $dsn,
            '--url', sprintf('%s://%s:%s', $node['protocol'], $node['host'], $node['port']),
            '--key', $typesense['api_key'],
        ], timeout: 300);

        $exitCode = $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if ($exitCode !== 0) {
            $this->error('seedgen reset failed.');

            return self::FAILURE;
        }

        $this->info('Talent demo data ready: 13k jobseekers seeded and indexed.');

        return self::SUCCESS;
    }
}
