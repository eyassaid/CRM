<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// An invoice is stored as data only; its PDF is rendered on demand (download, completion email).
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = ['project_id', 'created_by', 'items'];

    protected function casts(): array
    {
        return [
            // [['name' => ..., 'cost' => ...], ...]
            'items' => 'array',
        ];
    }

    /**
     * Keeps only the name and cost of each line, with the cost as a number.
     */
    protected function items(): Attribute
    {
        return Attribute::make(
            set: fn (array $items) => json_encode(array_map(fn (array $item) => [
                'name' => $item['name'],
                'cost' => (float) $item['cost'],
            ], array_values($items)), JSON_PRESERVE_ZERO_FRACTION),
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Sum of the items' costs.
     */
    public function total(): float
    {
        return round(array_sum(array_column($this->items, 'cost')), 2);
    }

    /**
     * Renders the invoice PDF from resources/views/invoices/pdf.blade.php.
     */
    public function pdf(): DomPdf
    {
        $this->loadMissing(['project.client.team', 'creator']);

        return Pdf::loadView('invoices.pdf', ['invoice' => $this]);
    }
}
