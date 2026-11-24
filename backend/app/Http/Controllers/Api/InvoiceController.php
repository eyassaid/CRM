<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

// Access to an invoice follows its project (see ProjectPolicy). Invoices are stored as items only; the PDF is
// rendered on download (InvoicePdfController).
class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $invoices = $project->invoices()->with('creator')->latest()->paginate();

        return InvoiceResource::collection($invoices)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Project $project, InvoiceRequest $request): JsonResponse
    {
        Gate::authorize('update', $project);
        abort_unless($project->isApproved(), 409, 'The project is waiting for the team leader\'s approval.');

        $invoice = $project->invoices()->create([
            'items' => $request->validated('items'),
            'created_by' => $request->user()->id,
        ]);

        return (new InvoiceResource($invoice->load('creator')))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice->project);

        return (new InvoiceResource($invoice->load('creator')))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('update', $invoice->project);

        $invoice->update(['items' => $request->validated('items')]);

        return (new InvoiceResource($invoice->load('creator')))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice): Response
    {
        Gate::authorize('update', $invoice->project);

        $invoice->delete();

        return response()->noContent();
    }
}
