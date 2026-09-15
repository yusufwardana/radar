<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Radar\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__.'/../src/Radar/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($path)) {
        require $path;
    }
});

use Radar\Adapters\JsonApiAdapter;
use Radar\Adapters\RssAdapter;
use Radar\Domain\Normalizer;
use Radar\Domain\Snapshot;
use Radar\Domain\SourceDefinition;
use Radar\Engine\ChangeEngine;
use Radar\Engine\SignalFactory;

$passed = 0;
$failed = 0;
$assert = function (bool $condition, string $message) use (&$passed, &$failed): void {
    if (!$condition) {
        $failed++;
        fwrite(STDERR, "FAIL: {$message}\n");
        return;
    }
    $passed++;
};

$source = new SourceDefinition('test-api', 'Test API', 'Test', 'API');
$adapter = new JsonApiAdapter($source, fn (): array => ['data' => [['id' => '1', 'title' => ' Alert ', 'description' => 'A change.']]]);
$items = $adapter->normalize($adapter->fetch());
$assert(count($items) === 1 && $items[0]->title === 'Alert', 'JSON adapter normalizes a structured item');
$assert(Normalizer::text('<p>Hello</p>  WORLD') === 'hello world', 'normalization removes markup and whitespace noise');
$assert(Normalizer::url('https://Example.test/a?utm_source=x&b=2') === 'https://example.test/a?b=2', 'URL normalization removes tracking parameters');

$rss = new RssAdapter(new SourceDefinition('test-rss', 'Test RSS', 'Test', 'RSS'), fn (): string => '<rss><channel><item><title>News</title><link>https://example.test/news</link></item></channel></rss>');
$assert(count($rss->normalize($rss->fetch())) === 1, 'RSS adapter parses a mocked feed');

$now = new DateTimeImmutable('2026-09-09T00:00:00Z');
$oldItem = $items[0];
$old = new Snapshot('test-api', $oldItem, $oldItem->normalizedHash(), $now);
$newItem = new \Radar\Domain\NormalizedItem('1', ' Alert ', 'A meaningful change.', null, null);
$new = new Snapshot('test-api', $newItem, $newItem->normalizedHash(), $now);
$engine = new ChangeEngine();
$noOp = $engine->compare(['1' => $old], [$old]);
$assert($noOp === [], 'same normalized content produces no change');
$changes = $engine->compare(['1' => $old], [$new]);
$assert(count($changes) === 1 && $changes[0]->type === 'TEXT_CHANGED', 'text change is classified deterministically');
$signal = (new SignalFactory())->fromChange($changes[0]);
$assert($signal->confidenceScore !== $signal->importanceScore && $signal->sources[0]['source'] === 'test-api', 'signal keeps separate scores and provenance');

fwrite(STDOUT, "{$passed} passed, {$failed} failed\n");
exit($failed === 0 ? 0 : 1);