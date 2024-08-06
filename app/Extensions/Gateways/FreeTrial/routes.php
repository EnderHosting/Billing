<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\ExtensionHelper;
use App\Models\Invoice;


Route::get('/freetrial/activate/{invoice_id}', function ($invoice_id) {
    $max_order_value = ExtensionHelper::getConfig('FreeTrial', 'max_order_value');
    $invoice = Invoice::find($invoice_id);
    if (!$invoice) {
        return redirect()->route('clients.invoice.index')->with('error', 'Invoice not found');
    }
    $total = $invoice->total();

    if ($total > $max_order_value) {
        return redirect()->route('clients.invoice.index')->with('error', 'This invoice is not eligible for free trial');
    }

    ExtensionHelper::paymentDone($invoice_id);
    return redirect()->route('clients.invoice.index')->with('success', 'Free trial activated');

})->name('FreeTrial.activate');
