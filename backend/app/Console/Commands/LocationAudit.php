<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\LocationProviderMapping;
use App\Radar\Locations\AdministrativeCode;
use Illuminate\Console\Command;

class LocationAudit extends Command
{
    protected $signature = 'radar:locations:audit';
    protected $description = 'Validate canonical location hierarchy and provider mappings.';

    public function handle(): int
    {
        $errors = 0;
        $seen = [];
        foreach (Location::query()->where('is_active', true)->cursor() as $location) {
            if (isset($seen[$location->code])) { $this->error("Duplicate code: {$location->code}"); $errors++; }
            $seen[$location->code] = true;
            try { $code = AdministrativeCode::forLevel($location->code, $location->level); } catch (\Throwable) { $this->error("Invalid code: {$location->code}"); $errors++; continue; }
            if ($location->parent_id !== null && !$location->parent()->exists()) { $this->error("Missing parent: {$location->code}"); $errors++; }
            if ($location->parent_id !== null && $code->parent() !== null && !$location->parent()->where('code', $code->parent()->display)->exists()) { $this->error("Broken hierarchy: {$location->code}"); $errors++; }
            if ($location->parent_id !== null && $location->parent?->level === $location->level) { $this->error("Invalid parent level: {$location->code}"); $errors++; }
        }
        $duplicateMappings = LocationProviderMapping::query()->select('provider', 'provider_code')->groupBy('provider', 'provider_code')->havingRaw('COUNT(*) > 1')->count();
        if ($duplicateMappings > 0) { $this->error("Duplicate provider mappings: {$duplicateMappings}"); $errors += $duplicateMappings; }
        $this->info("Location audit errors: {$errors}");
        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }
}