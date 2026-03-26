<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiContractTest extends TestCase
{
    public function test_openapi_file_is_valid_json_and_has_minimum_structure(): void
    {
        $specPath = base_path('docs/openapi/openapi.json');
        $this->assertFileExists($specPath);

        $raw = file_get_contents($specPath);
        $this->assertNotFalse($raw);

        $spec = json_decode((string) $raw, true);
        $this->assertIsArray($spec);
        $this->assertSame('3.0.3', $spec['openapi'] ?? null);
        $this->assertSame('Aluminio ERP API', $spec['info']['title'] ?? null);
        $this->assertSame('1.0.0', $spec['info']['version'] ?? null);
        $this->assertIsArray($spec['paths'] ?? null);
        $this->assertNotEmpty($spec['paths']);
    }

    public function test_documented_operations_match_routes_file(): void
    {
        $specPath = base_path('docs/openapi/openapi.json');
        $raw = file_get_contents($specPath);
        $spec = json_decode((string) $raw, true);

        $routesSource = file_get_contents(base_path('routes/api.php'));
        $this->assertNotFalse($routesSource);

        $operations = [
            ['path' => '/api/login', 'method' => 'post', 'route' => '/login'],
            ['path' => '/api/me', 'method' => 'get', 'route' => '/me'],
            ['path' => '/api/dashboard/resumo', 'method' => 'get', 'route' => '/dashboard/resumo'],
            ['path' => '/api/clientes', 'method' => 'get', 'route' => '/clientes'],
            ['path' => '/api/clientes', 'method' => 'post', 'route' => '/clientes'],
            ['path' => '/api/clientes/{cliente}', 'method' => 'get', 'route' => '/clientes/{cliente}'],
            ['path' => '/api/clientes/{cliente}', 'method' => 'put', 'route' => '/clientes/{cliente}'],
            ['path' => '/api/produtos', 'method' => 'get', 'route' => '/produtos'],
            ['path' => '/api/produtos', 'method' => 'post', 'route' => '/produtos'],
            ['path' => '/api/orcamentos', 'method' => 'get', 'route' => '/orcamentos'],
            ['path' => '/api/orcamentos', 'method' => 'post', 'route' => '/orcamentos'],
            ['path' => '/api/vendas', 'method' => 'get', 'route' => '/vendas'],
            ['path' => '/api/vendas', 'method' => 'post', 'route' => '/vendas'],
            ['path' => '/api/contas-a-pagar', 'method' => 'get', 'route' => '/contas-a-pagar'],
            ['path' => '/api/contas-a-pagar', 'method' => 'post', 'route' => '/contas-a-pagar'],
            ['path' => '/api/contas-a-receber', 'method' => 'get', 'route' => '/contas-a-receber'],
            ['path' => '/api/fluxo-caixa', 'method' => 'get', 'route' => '/fluxo-caixa'],
            ['path' => '/api/operacao/health', 'method' => 'get', 'route' => '/operacao/health'],
            ['path' => '/api/operacao/readiness', 'method' => 'get', 'route' => '/operacao/readiness'],
            ['path' => '/api/operacao/preflight', 'method' => 'get', 'route' => '/operacao/preflight'],
            ['path' => '/api/operacao/backup', 'method' => 'post', 'route' => '/operacao/backup'],
            ['path' => '/api/operacao/backup/verificar', 'method' => 'post', 'route' => '/operacao/backup/verificar'],
        ];

        foreach ($operations as $operation) {
            $path = $operation['path'];
            $method = $operation['method'];
            $route = $operation['route'];

            $this->assertArrayHasKey($path, $spec['paths']);
            $this->assertArrayHasKey($method, $spec['paths'][$path]);
            $this->assertStringContainsString("Route::{$method}('{$route}'", (string) $routesSource);
        }
    }

    public function test_protected_endpoints_define_security_scheme(): void
    {
        $specPath = base_path('docs/openapi/openapi.json');
        $raw = file_get_contents($specPath);
        $spec = json_decode((string) $raw, true);

        $this->assertArrayHasKey('securitySchemes', $spec['components'] ?? []);
        $this->assertArrayHasKey('BearerToken', $spec['components']['securitySchemes'] ?? []);

        foreach (($spec['paths'] ?? []) as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if ($path === '/api/login' && $method === 'post') {
                    continue;
                }

                $this->assertArrayHasKey('security', $operation, "Endpoint {$method} {$path} sem security");
            }
        }
    }
}
