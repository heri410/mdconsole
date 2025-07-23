<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $invoices = collect();

        // Rechnungen nur für Kunden laden, die eine customer_id haben
        if ($user->role === 'customer' && $user->customer_id) {
            $query = Invoice::where('customer_id', $user->customer_id);

            // Filter anwenden
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            if ($request->filled('search')) {
                $query->where('number', 'like', '%' . $request->search . '%');
            }

            // Sortierung und Paginierung
            $invoices = $query->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('dashboard', compact('invoices'));
    }

    public function downloadPdf($invoiceId)
    {
        $user = Auth::user();

        $invoice = Invoice::where('id', $invoiceId)
            ->where('customer_id', $user->customer_id)
            ->first();
        
        if (!$invoice) { 
            abort(404, 'Rechnung nicht gefunden');
        }

        return Storage::download('invoices/' . $invoice->number . '.pdf');
    }
}