<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SoftwareRequestController extends Controller
{
    public function index()
    {
        return view('software-request.index');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'project_type' => 'required|string',
            'budget_range' => 'required|string',
            'deadline' => 'nullable|string|max:255',
            'description' => 'required|string',
            'features_list' => 'nullable|string',
        ]);

        \App\Models\SoftwareRequest::create($validated);

        // Opcional: Enviar e-mail para admin (ex: Mail::to('admin@adassoft.com')->send(...))

        return redirect()->route('software-request.index')
            ->with('success', 'Sua solicitação foi enviada com sucesso! Analisaremos seu projeto e entraremos em contato em breve.');
    }
}
