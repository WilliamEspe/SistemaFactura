<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\ClienteAccessToken;
use Illuminate\Support\Facades\Log;

class RegenerarTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regenerar:tokens {--cliente=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera los tokens para todos los clientes o uno específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clienteNombre = $this->option('cliente');
        
        if ($clienteNombre) {
            // Regenerar token para un cliente específico
            $cliente = Cliente::where('nombre', $clienteNombre)->first();
            if (!$cliente) {
                $this->error("Cliente '{$clienteNombre}' no encontrado");
                return 1;
            }
            $this->regenerarTokenCliente($cliente);
        } else {
            // Regenerar tokens para todos los clientes
            $clientes = Cliente::all();
            foreach ($clientes as $cliente) {
                $this->regenerarTokenCliente($cliente);
            }
        }
        
        $this->info('¡Tokens regenerados exitosamente!');
        return 0;
    }
    
    private function regenerarTokenCliente($cliente)
    {
        $this->info("Regenerando token para: {$cliente->nombre}");
        
        // Eliminar tokens existentes
        ClienteAccessToken::where('cliente_id', $cliente->id)->delete();
        $this->line("  - Tokens anteriores eliminados");
        
        // Crear nuevo token
        $tokenData = ClienteAccessToken::createToken($cliente, 'Token Regenerado Automático');
        $token = $tokenData['token'];
        $plainTextToken = $tokenData['plainTextToken'];
        
        $this->line("  - Nuevo token creado");
        $this->line("  - Token plano: {$plainTextToken}");
        $this->line("  - Token hash: {$token->token}");
        $this->line("  - Almacenado en BD: {$token->plain_text_token}");
        
        // Verificar que se guardó correctamente
        if ($token->plain_text_token === $plainTextToken) {
            $this->line("  - ✅ Token plano guardado correctamente");
        } else {
            $this->error("  - ❌ Error: Token plano no se guardó correctamente");
            $this->error("    Esperado: {$plainTextToken}");
            $this->error("    Guardado: {$token->plain_text_token}");
        }
        
        // Verificar hash
        $hashCalculado = hash('sha256', $plainTextToken);
        if ($token->token === $hashCalculado) {
            $this->line("  - ✅ Hash del token correcto");
        } else {
            $this->error("  - ❌ Error: Hash del token incorrecto");
        }
        
        Log::info("Token regenerado para cliente {$cliente->nombre}", [
            'cliente_id' => $cliente->id,
            'token_id' => $token->id,
            'plain_text_token' => $plainTextToken,
            'hash' => $token->token
        ]);
        
        $this->line("");
    }
}
