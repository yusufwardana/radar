<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SignalResource;
use App\Models\Signal;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SignalController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SignalResource::collection(Signal::query()->with(['sources', 'events'])->latest('last_updated_at')->paginate(25));
    }

    public function show(Signal $signal): SignalResource
    {
        return new SignalResource($signal->load(['sources', 'events']));
    }
}