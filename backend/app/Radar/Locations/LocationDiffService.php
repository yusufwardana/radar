<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use App\Models\LocationDataset;

final class LocationDiffService
{
    /** @return array<string,mixed> */
    public function compare(LocationDataset $old, LocationDataset $new): array
    {
        $left = $old->locations()->with('parent')->get(['id', 'code', 'name', 'level', 'parent_id'])->keyBy('code');
        $right = $new->locations()->with('parent')->get(['id', 'code', 'name', 'level', 'parent_id'])->keyBy('code');
        $added = $right->keys()->diff($left->keys())->values()->all();
        $removed = $left->keys()->diff($right->keys())->values()->all();
        $renamed = [];
        $levelChanged = [];
        $parentChanged = [];
        foreach ($left->intersectByKeys($right) as $code => $location) {
            $current = $right->get($code);
            if ($location->name !== $current->name) $renamed[] = ['code' => $code, 'old' => $location->name, 'new' => $current->name];
            if ($location->level->value !== $current->level->value) $levelChanged[] = ['code' => $code, 'old' => $location->level->value, 'new' => $current->level->value];
            $oldParent = $location->parent?->code;
            $newParent = $current->parent?->code;
            if ($oldParent !== $newParent) $parentChanged[] = ['code' => $code, 'old_parent_code' => $oldParent, 'new_parent_code' => $newParent];
        }
        return ['old_dataset' => $old->version, 'new_dataset' => $new->version, 'added' => $added, 'removed' => $removed, 'renamed' => $renamed, 'level_changed' => $levelChanged, 'parent_changed' => $parentChanged, 'unchanged' => max(0, $left->intersectByKeys($right)->count() - count($renamed) - count($levelChanged) - count($parentChanged)), 'ambiguous' => []];
    }
}