<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SourceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SourceResource::collection(Source::query()->latest('id')->paginate(25));
    }

    public function show(Source $source): SourceResource
    {
        return new SourceResource($source);
    }
}