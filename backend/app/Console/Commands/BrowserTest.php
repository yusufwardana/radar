<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Radar\Crawler\BrowserWorkerClient;
use Illuminate\Console\Command;

class BrowserTest extends Command
{
    protected $signature = 'radar:browser:test {fixture=dynamic}';
    protected $description = 'Run a controlled Playwright fixture through BrowserWorkerClient.';

    public function handle(BrowserWorkerClient $client): int
    {
        try {
            $result = $client->crawlFixture(1, (string) $this->argument('fixture'));
            $this->info('Browser Worker: OK');
            $this->line('Title: '.($result->title ?? 'unknown'));
            $this->line('Links: '.count($result->links));
            $this->line('Documents: '.count($result->documents));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Browser Worker: FAILED '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}