<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceDownloadController extends Controller
{
    public function __invoke(Request $request, string $invoiceId): Response
    {
        return $request->user()->downloadInvoice($invoiceId);
    }
}
