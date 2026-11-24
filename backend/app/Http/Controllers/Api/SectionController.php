<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sections\SectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

// Admin-only (see routes/api.php).
class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $sections = Section::with('teams')->paginate();

        return SectionResource::collection($sections)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SectionRequest $request): JsonResponse
    {
        $section = Section::create($request->validated());

        return (new SectionResource($section))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Section $section): JsonResponse
    {
        return (new SectionResource($section->load('teams')))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SectionRequest $request, Section $section): JsonResponse
    {
        $section->update($request->validated());

        return (new SectionResource($section->refresh()))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Section $section): Response
    {
        $section->delete();

        return response()->noContent();
    }
}
