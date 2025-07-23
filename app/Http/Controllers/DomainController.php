<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainController extends Controller
{
    /**
     * Display a listing of the customer's domains.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Only customers can access their own domains
        if ($user->role !== 'customer' || !$user->customer_id) {
            abort(403, 'Unauthorized access to domains');
        }
        
        $query = Domain::where('customer_id', $user->customer_id)
                      ->with('provider');
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $query->where('fqdn', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('expiring_soon')) {
            $query->expiringSoon();
        }
        
        if ($request->filled('expired')) {
            $query->expired();
        }
        
        // Sort by due date (earliest first for expiring domains)
        $domains = $query->orderBy('due_date', 'asc')
                        ->orderBy('fqdn', 'asc')
                        ->paginate(10);
        
        return view('domains.index', compact('domains'));
    }
    
    /**
     * Display the specified domain.
     */
    public function show(Domain $domain)
    {
        $user = Auth::user();
        
        // Ensure customer can only see their own domains
        if ($user->role !== 'customer' || $domain->customer_id !== $user->customer_id) {
            abort(403, 'Unauthorized access to domain');
        }
        
        return view('domains.show', compact('domain'));
    }
}
