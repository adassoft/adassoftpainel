<?php

namespace App\Filament\App\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

use Livewire\Attributes\Layout;

#[Layout('layouts.login-split')]
class Login extends BaseLogin
{
    protected static string $view = 'filament.app.pages.auth.login';
}
