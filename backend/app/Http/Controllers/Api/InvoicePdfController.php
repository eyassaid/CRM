<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

// GET /invoices/{invoice}/pdf: renders the invoice PDF from its stored items; nothing is saved to disk.
class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice->project);

        return $invoice->pdf()->download("invoice-{$invoice->id}.pdf");
    }
}
