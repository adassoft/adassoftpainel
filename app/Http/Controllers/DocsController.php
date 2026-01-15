<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocsController extends Controller
{
    public function delphi()
    {
        return view('docs.delphi');
    }

    public function java()
    {
        return view('docs.java');
    }

    public function lazarus()
    {
        return view('docs.lazarus');
    }

    public function index()
    {
        return view('docs.index');
    }
}
