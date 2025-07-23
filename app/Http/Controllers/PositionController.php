<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Customer;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Position::with('customer');
        
        // Filter nach Kunde
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        // Filter nach Status (abgerechnet/nicht abgerechnet)
        if ($request->filled('billed')) {
            $query->where('billed', $request->billed === '1');
        }
        
        // Suche nach Name
        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }
        
        $positions = $query->orderBy('created_at', 'desc')->paginate(20);
        $customers = Customer::orderBy('company_name')->get();
        
        return view('positions.index', compact('positions', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('company_name')->get();
        return view('positions.create', compact('customers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0.01',
            'unit_name' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);
        
        $validated['discount'] = $validated['discount'] ?? 0;
        
        Position::create($validated);
        
        return redirect()->route('positions.index')
            ->with('success', 'Position wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position)
    {
        $position->load('customer');
        return view('positions.show', compact('position'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Position $position)
    {
        $customers = Customer::orderBy('company_name')->get();
        return view('positions.edit', compact('position', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position)
    {
        // Verhindere Bearbeitung bereits abgerechneter Positionen
        if ($position->billed) {
            return redirect()->route('positions.index')
                ->with('error', 'Abgerechnete Positionen können nicht bearbeitet werden.');
        }
        
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0.01',
            'unit_name' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);
        
        $validated['discount'] = $validated['discount'] ?? 0;
        
        $position->update($validated);
        
        return redirect()->route('positions.index')
            ->with('success', 'Position wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        // Verhindere Löschung bereits abgerechneter Positionen
        if ($position->billed) {
            return redirect()->route('positions.index')
                ->with('error', 'Abgerechnete Positionen können nicht gelöscht werden.');
        }
        
        $position->delete();
        
        return redirect()->route('positions.index')
            ->with('success', 'Position wurde erfolgreich gelöscht.');
    }
}
