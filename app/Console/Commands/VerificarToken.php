<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;

class VerificarToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verificar:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar tokens del cliente de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando tokens del cliente de prueba...');
        
        $cliente = Cliente::where('email', 'cliente.test@ejemplo.com')->first();
        if (!$cliente) {
            $this->error('Cliente de prueba no encontrado');
            return;
        }
        
        $this->info("Cliente: {$cliente->nombre}");
        
        // Obtener tokens del cliente
        $tokens = ClienteAccessToken::where('cliente_id', $cliente->id)->get();
        $this->info("Tokens encontrados: {$tokens->count()}");
        
        foreach ($tokens as $token) {
            $this->info("=== Token ID: {$token->id} ===");
            $this->line("Nombre: {$token->name}");
            $this->line("Token plano: " . ($token->plain_text_token ? substr($token->plain_text_token, 0, 10) . '...' : 'VACIO'));
            $this->line("Token completo: " . ($token->plain_text_token ?? 'NULO'));
            $this->line("Token hash: {$token->token}");
            $this->line("Expira: " . ($token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : 'Sin expiración'));
            
            // Debug adicional
            $this->line("=== DEBUG INFO ===");
            $this->line("Tipo de plain_text_token: " . gettype($token->plain_text_token));
            $this->line("Longitud de plain_text_token: " . strlen($token->plain_text_token ?? ''));
            $this->line("Plain text token raw: '{$token->plain_text_token}'");
            
            $isActive = !$token->expires_at || $token->expires_at->isFuture();
            $this->line("Activo: " . ($isActive ? 'SI' : 'NO'));
            
            if ($token->plain_text_token) {
                $hashCalculado = hash('sha256', $token->plain_text_token);
                $this->line("Hash calculado: {$hashCalculado}");
                $hashCoincide = $token->token === $hashCalculado;
                $this->line("Hash coincide: " . ($hashCoincide ? 'SI' : 'NO'));
                
                if (!$hashCoincide) {
                    $this->error("PROBLEMA: El hash no coincide!");
                    $this->error("Hash esperado: {$token->token}");
                    $this->error("Hash calculado: {$hashCalculado}");
                }
            } else {
                $this->error("PROBLEMA: plain_text_token está vacío!");
                $hashCalculado = hash('sha256', '');
                $this->line("Hash calculado (vacío): {$hashCalculado}");
                $this->line("Hash coincide: NO");
            }
            
            $this->line("");
        }
    }
}
