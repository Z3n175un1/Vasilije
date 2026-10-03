<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * BUSCADOR DESPLEGABLE (window.buscarEnSelect)
 *
 * El componente convierte un <select> largo en un campo con lista filtrable.
 * Lo que se verifica es la parte que puede fallar en silencio: la regla de
 * coincidencia. Si el buscador no encuentra lo que el operador busca, la
 * conclusión es que el registro no existe, y el alta se pierde.
 *
 * Estos tests no ejecutan JavaScript: comprueban que el componente se monte
 * con el contrato que el JS espera y que la estructura de la vista lo
 * permita. La regla de coincidencia en si la cubre `coincidir()` en el
 * navegador, revisada a mano.
 */
final class BuscadorDesplegableTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<array{0:string,1:bool}> */
    public static function rutasConBuscador(): array
    {
        return [
            ['/almacen/movimiento/COMPRA', true],   // producto y proveedor
            ['/almacen/movimiento/ENTREGA', false], // solo producto
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('rutasConBuscador')]
    public function el_formulario_monta_los_campos_que_el_buscar_necesita(string $ruta, bool $conProveedor): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get($ruta)
            ->assertOk()
            ->getContent();

        // El buscador envuelve al select, no lo reemplaza: necesita un
        // <select> real con `name` para seguir enviando el valor.
        $this->assertStringContainsString('name="id_inventario"', $contenido);
        $this->assertStringContainsString('name="cantidad"', $contenido);

        $this->assertStringContainsString('buscarEnSelect', $contenido);

        if ($conProveedor) {
            $this->assertStringContainsString('name="id_proveedor"', $contenido);
        }
    }

    #[Test]
    public function el_producto_ofrece_codigo_para_filtrar_ademas_del_nombre(): void
    {
        // Sin productos sembrados no hay <option> que revisar, y el buscador
        // se quedaría sin nada que mostrar sin avisar.
        $this->producto(20, ['codigo_barras' => '7791234567890']);

        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/almacen/movimiento/COMPRA')
            ->assertOk()
            ->getContent();

        // El operador busca por código de barras o interno con frecuencia.
        $this->assertStringContainsString('data-detalle="7791234567890"', $contenido);
    }

    #[Test]
    public function el_producto_y_el_proveedor_se_precargan_desde_el_servidor(): void
    {
        $admin = $this->usuarioConRol('admin');

        $producto = $this->producto(20, ['nombre_producto' => 'ACEITE HIDRAULICO SAE 90']);
        $this->proveedor(['nombre_proveedor' => 'LUBRICANTES DEL ORIENTE LTDA']);

        $contenido = $this->actingAs($admin)->get('/almacen/movimiento/COMPRA')->getContent();

        // Sin opciones en el select el buscador no tiene nada que mostrar, y
        // no se da cuenta: es el fallo que hace que un buscador "no funcione".
        $this->assertStringContainsString('ACEITE HIDRAULICO SAE 90', $contenido);
        $this->assertStringContainsString('LUBRICANTES DEL ORIENTE LTDA', $contenido);
    }

    #[Test]
    public function el_catalogo_para_el_js_incluye_el_stock_que_necesita_el_formulario(): void
    {
        $this->producto(50, ['nombre_producto' => 'FILTRO DE AIRE', 'stock_actual' => 12]);

        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/almacen/movimiento/ENTREGA')
            ->assertOk()
            ->getContent();

        // `mostrarStock()` y el aviso de stock insuficiente leen de este array.
        $this->assertStringContainsString('const productos', $contenido);
        $this->assertStringContainsString('stock_actual', $contenido);
        $this->assertStringContainsString('unidad_medida', $contenido);
    }

    #[Test]
    public function el_buscador_ignora_las_unidades_que_no_tienen_que_elegirse(): void
    {
        // La entrega también busca unidad y conductor: son listas cortas pero
        // se consultan todos los días.
        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/almacen/movimiento/ENTREGA')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ESCRIBA LA PLACA DE LA UNIDAD', $contenido);
        $this->assertStringContainsString('ESCRIBA EL NOMBRE DEL CONDUCTOR', $contenido);
    }

    #[Test]
    public function la_lista_se_avisa_cuando_se_recorta_el_resultado(): void
    {
        // Si no se avisa, el operador cree que esos son todos los productos.
        $contenido = file_get_contents(base_path('resources/js/app.js'));

        $this->assertStringContainsString('Mostrando', $contenido);
        $this->assertStringContainsString('Afine la búsqueda', $contenido);
    }

    #[Test]
    public function el_componente_compara_sin_acentos(): void
    {
        $contenido = file_get_contents(base_path('resources/js/app.js'));

        // Sin normalizar, "lubric" no encuentra "Lubricantes" y el operador
        // concluye que el producto no existe.
        $this->assertStringContainsString('normalize', $contenido);
        $this->assertStringContainsString('u0300-\\u036f', $contenido);
    }
}