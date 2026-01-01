<?php

namespace App\Livewire;

use App\Models\License;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Filament\Notifications\Notification;

class ManageLicenseTerminals extends Component
{
    public $licenseId;
    public $terminals = [];
    public $confirmingMac = null;

    public function mount($licenseId)
    {
        $this->licenseId = $licenseId;
        $this->loadTerminals();
    }

    public function confirmRemoval($mac)
    {
        $this->confirmingMac = $mac;
    }

    public function cancelRemoval()
    {
        $this->confirmingMac = null;
    }

    public function loadTerminals()
    {
        $license = License::find($this->licenseId);
        if ($license) {
            $this->terminals = $license->merged_terminals;
        }
    }

    public function removeTerminal($mac, $source)
    {
        if (!$this->licenseId || !$mac)
            return;

        try {
            if ($source === 'terminal') {
                // Remover vínculo da tabela terminais_software
                $terminal = DB::table('terminais')->where('MAC', $mac)->first();

                if ($terminal) {
                    DB::table('terminais_software')
                        ->where('licenca_id', $this->licenseId)
                        ->where('terminal_codigo', $terminal->CODIGO)
                        ->delete();

                    $this->updateLicenseCount();
                }
            } elseif ($source === 'installation') {
                // Remover da tabela licenca_instalacoes
                DB::table('licenca_instalacoes')
                    ->where('licenca_id', $this->licenseId)
                    ->where('mac_address', $mac)
                    ->delete();
            }

            Notification::make()
                ->title('Terminal removido')
                ->success()
                ->send();

            $this->confirmingMac = null;
            $this->loadTerminals();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao remover')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function updateLicenseCount()
    {
        // Recalcula o total real
        $count = DB::table('terminais_software')
            ->where('licenca_id', $this->licenseId)
            ->where('ativo', 1) // Geralmente conta apenas ativos? Ou todos vinculados?
            // O código legado decrementa apenas, ideal é contar merge. 
            // Vamos assumir decremento simples ou recalculo.
            ->count();

        // Atualiza na licença
        DB::table('licencas_ativas')
            ->where('id', $this->licenseId)
            ->update(['terminais_utilizados' => $count]);
    }

    public function render()
    {
        return view('livewire.manage-license-terminals');
    }
}
