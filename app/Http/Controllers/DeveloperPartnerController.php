<?php

namespace App\Http\Controllers;

use App\Models\DeveloperLead;
use Illuminate\Http\Request;

class DeveloperPartnerController extends Controller
{
    public function index()
    {
        return view('landing.developer');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:developer_leads,email', // Prevent duplicates (MVP)
            'whatsapp' => 'nullable|string|max:20',
            'software_description' => 'nullable|string|max:1000',
        ]);

        DeveloperLead::create($validated);

        return back()->with('success', 'Cadastro recebido! Entraremos em contato em breve para o onboarding.');
    }
}
