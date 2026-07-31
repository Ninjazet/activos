<?php

/**
 * Convierte el formulario de equipos en datos normalizados para el servicio.
 */
final class EquipoFormulario {
    public static function crear(array $origen): array {
        return self::normalizar($origen, false);
    }

    public static function editar(array $origen): array {
        return self::normalizar($origen, true);
    }

    private static function normalizar(array $origen, bool $edicion): array {
        $sufijo = $edicion ? 'Act' : '';
        $marcaCampo = $edicion ? 'marcaAct' : 'idmarca';
        $modeloCampo = $edicion ? 'modeloAct' : 'idmodelo';

        $fechaCompra = Validacion::fechaOpcional(
            $origen['fecha_compra' . $sufijo] ?? null,
            'La fecha de compra'
        );
        $garantia = Validacion::fechaOpcional(
            $origen['vencimiento_garantia' . $sufijo] ?? null,
            'El vencimiento de garantía'
        );
        Validacion::validarOrdenFechas($fechaCompra, $garantia);

        $estado = EquipoEstado::DISPONIBLE;
        if ($edicion) {
            $estadoRecibido = filter_var(
                $origen['estado_equipoAct'] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($estadoRecibido !== false && EquipoEstado::esValido((int)$estadoRecibido)) {
                $estado = (int)$estadoRecibido;
            }
        }

        return [
            'id' => $edicion
                ? Validacion::enteroPositivo($origen['idequipo'] ?? null, 'El equipo')
                : null,
            'idmarca' => Validacion::enteroPositivo($origen[$marcaCampo] ?? null, 'La marca'),
            'idmodelo' => Validacion::enteroPositivo($origen[$modeloCampo] ?? null, 'El modelo'),
            'fecha_compra' => $fechaCompra,
            'costo' => Validacion::costoOpcional($origen['costo' . $sufijo] ?? null),
            'factura' => Validacion::textoOpcional($origen['factura' . $sufijo] ?? null, 100, 'La factura'),
            'garantia' => $garantia,
            'numero_serie' => Validacion::numeroSerieOpcional($origen['numero_serie' . $sufijo] ?? null),
            'tipo_equipo' => Validacion::textoOpcional($origen['tipo_equipo' . $sufijo] ?? null, 50, 'El tipo de equipo') ?? 'Otro',
            'estado' => $estado,
        ];
    }
}
