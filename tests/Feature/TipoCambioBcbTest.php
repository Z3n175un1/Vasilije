<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ConfiguracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * TIPO DE CAMBIO OFICIAL (BCB) EN CONFIGURACION.
 *
 * La version en PHP vive en BcbService y config extra:
 *  a. Usar el monto oficial al tipo de cambio (BCB) para el dia de hoy.
 *  b. Usar el registro oficial guardado por fecha de flete (HISTORICO).
 *
 * La opcion MANUAL (digitar el valor a mano) se retiro de la pantalla:
 * causaba errores de digitacion. El campo tipo_cambio queda bloqueado y
 * solo sirve como respaldo si el BCB no responde.
 */
class TipoCambioBcbTest extends TestCase
{
    use RefreshDatabase;

    private const HTML_FIJO = <<<'HTML'
    <html><body>
      <div class="bcb-tco-amount"><span class="bcb-tco-num">11,73</span></div>
      <div class="bcb-kpi2-asof">Vigencia al 21 de octubre, 2026</div>
    </body></html>
    HTML;

    private const HTML_SIN_TARJETA = '<html><body><div class="otro">nada</div></body></html>';

    #[Test]
    public function el_html_del_bcb_se_puede_procesar_sin_red(): void
    {
        $datos = (new \App\Services\BcbService())->parsear(self::HTML_FIJO);

        $this->assertNotNull($datos);
        $this->assertSame(11.73, $datos['valor']);
        $this->assertSame('11,73', $datos['valor_texto']);
        $this->assertSame('2026-10-21', $datos['fecha_iso']);
        $this->assertSame('Miércoles 21 de octubre de 2026', $datos['fecha']);
    }

    #[Test]
    public function el_html_sin_valor_oficial_devuelve_null(): void
    {
        $this->assertNull((new \App\Services\BcbService())->parsear(self::HTML_SIN_TARJETA));
    }

    #[Test]
    public function por_defecto_la_fuente_es_historico_y_el_manual_queda_como_respaldo(): void
    {
        $config = new ConfiguracionService();

        $this->assertSame('HISTORICO', $config->fuenteTipoCambio());
        $this->assertSame(6.96, $config->tipoCambio());
    }

    /** Deja la fuente conicia en BCB (como lo haria el administrador via web). */
    private function fuenteEnBcb(): void
    {
        $llave = ConfiguracionService::FUENTE_TIPO_CAMBIO;
        DB::table('global.configuracion')->insert([
            'llave' => $llave, 'valor' => 'BCB', 'descripcion' => $llave, 'created_at' => now(),
        ]);
    }

    #[Test]
    public function con_fuente_bcb_manda_el_valor_oficial_del_cache(): void
    {
        $this->fuenteEnBcb();
        cache()->put('bcb_tipo_cambio', [
            'valor' => 11.73,
            'valor_texto' => '11,73',
            'fecha' => 'Miercoles 21 de octubre de 2026',
            'fecha_iso' => '2026-10-21',
        ], now()->addMinutes(20));

        $config = new ConfiguracionService(new \App\Services\BcbService());

        $this->assertSame(11.73, $config->tipoCambioVigente());
    }

    #[Test]
    public function si_el_bcb_no_responde_se_recurre_al_valor_digitado(): void
    {
        $this->fuenteEnBcb();

        Http::fake(['www.bcb.gob.bo/*' => Http::response('hogar caido', 500)]);

        // Sin cache y con el BCB inaccesible, los reportes nunca quedan sin
        // divisor dolares -> bolivianos: manda el valor digitado.
        $config = new ConfiguracionService(new \App\Services\BcbService());

        $this->assertSame(6.96, $config->tipoCambio(), 'El valor manual queda como respaldo.');
        $this->assertSame(6.96, $config->tipoCambioVigente());
    }

    #[Test]
    public function el_endpoint_del_tipo_cambio_devuelve_el_valor_oficial(): void
    {
        $this->fuenteEnBcb();
        Http::fake(['www.bcb.gob.bo/*' => Http::response(self::HTML_FIJO, 200)]);

        $respuesta = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/tipo-cambio-bcb?force=1')
            ->assertOk()
            ->json();

        $this->assertTrue($respuesta['success']);
        $this->assertSame(11.73, $respuesta['data']['valor']);
        $this->assertSame('2026-10-21', $respuesta['data']['fecha_iso']);

        // El valor queda cacheado para el resto de la aplicacion.
        $config = new ConfiguracionService(new \App\Services\BcbService());
        $this->assertSame(11.73, $config->tipoCambioVigente());
    }

    #[Test]
    public function el_endpoint_reporta_fallo_si_el_bcb_no_responde(): void
    {
        Http::fake(['www.bcb.gob.bo/*' => Http::response('servicio caido', 500)]);

        $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/tipo-cambio-bcb?force=1')
            ->assertStatus(503);
    }

    #[Test]
    public function la_configuracion_persiste_la_fuente_elegida(): void
    {
        $this->actingAs($this->usuarioConRol('admin'))->post('/configuracion', [
            'tipo_cambio' => '7.10',
            'precio_tonelada_usd' => '13',
            'valor_flota_por_unidad' => '0',
            'saldo_inicial_proveedores' => '0',
            'fuente_tipo_cambio' => 'BCB',
        ])->assertRedirect();

        $fuente = DB::table('global.configuracion')
            ->where('llave', ConfiguracionService::FUENTE_TIPO_CAMBIO)
            ->value('valor');

        $this->assertSame('BCB', $fuente);
    }

    #[Test]
    public function la_configuracion_rechaza_una_fuente_desconocida(): void
    {
        $this->actingAs($this->usuarioConRol('admin'))->post('/configuracion', [
            'tipo_cambio' => '7.10',
            'precio_tonelada_usd' => '13',
            'fuente_tipo_cambio' => 'CENTRAL DE BOLIVIA',
        ])->assertSessionHasErrors(['fuente_tipo_cambio']);
    }

    #[Test]
    public function la_vista_de_configuracion_ofrece_las_opciones_sin_la_manual(): void
    {
        $this->actingAs($this->usuarioConRol('admin'))->get('/configuracion')->assertOk()
            // La opcion de digitar el valor a mano fue retirada: es la puerta
            // a errores de capa 8 (el operador sobrescribia el TC judicial).
            ->assertDontSee('Ingresarlo manualmente')
            ->assertSee('Usar el valor oficial de hoy')
            ->assertSee('Usar el valor oficial del')
            ->assertSee('api/tipo-cambio-bcb');
    }

    #[Test]
    public function el_panel_del_valor_oficial_de_hoy_solo_aparece_con_la_fuente_bcb(): void
    {
        // Con HISTORICO el operador consulta un dia concreto: mostrar el
        // valor de HOY arriba confunde (la fecha del flete es la que manda).
        $this->fuenteEnHistorico();

        $html = $this->actingAs($this->usuarioConRol('admin'))->get('/configuracion')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div id="bcb-box" class="cfg-bcb ?">/', $html);
        $this->assertMatchesRegularExpression('/<div id="hist-consulta" class="cfg-hist is-visible">/', $html);
    }

    /** Deja la fuente fija en HISTORICO (como el administrador via web). */
    private function fuenteEnHistorico(): void
    {
        $llave = ConfiguracionService::FUENTE_TIPO_CAMBIO;
        DB::table('global.configuracion')->insert([
            'llave' => $llave, 'valor' => 'HISTORICO', 'descripcion' => $llave, 'created_at' => now(),
        ]);
    }
}
