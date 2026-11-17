<?php

namespace Database\Seeders;

use App\Models\EventType;
use App\Models\Package;
use App\Models\PackageService;
use App\Models\Service;
use App\Models\ServiceRate;
use Illuminate\Database\Seeder;

class RaicesCatalogSeeder extends Seeder
{
    private const VALID_FROM = '2026-10-18';

    public function run(): void
    {
        $eventNames = [
            'Bautizo', 'Quinceañera', 'Matrimonio', 'Graduación Universitaria', 'Baby Shower',
            'Corporativo', 'Graduación colegio inicial/primaria', 'Graduación colegio secundaria',
            'Fiesta Infantil', 'Reunión familiar', 'Cumpleaños', 'Aniversario',
        ];
        foreach ($eventNames as $name) {
            EventType::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        $packages = [
            'basico' => Package::firstOrCreate(['name' => 'basico'], ['description' => 'Paquete Básico', 'is_active' => true]),
            'estandar' => Package::firstOrCreate(['name' => 'estandar'], ['description' => 'Paquete Estándar', 'is_active' => true]),
            'premium' => Package::firstOrCreate(['name' => 'premium'], ['description' => 'Paquete Premium', 'is_active' => true]),
        ];

        $services = [];
        $sort = 0;
        $add = function (
            string $name,
            string $category,
            float $price,
            string $unit,
            array $packageKeys = ['basico', 'estandar', 'premium'],
            bool|array $optional = false,
            string $quantityRule = 'fixed',
            array $eventNamesForItem = [],
            ?string $selectionGroup = null,
            ?string $replacesKey = null,
            bool $displayQuantity = true,
            array $eventRates = [],
        ?float $initialQuantity = null,
        ) use (&$services, &$sort, $packages): Service {
            $key = $category . '|' . $name;
            $service = Service::query()->firstOrCreate(
                ['name' => $name, 'category' => $category],
                ['unit_label' => $unit, 'selection_group' => $selectionGroup, 'is_optional' => false, 'is_active' => true],
            );
            $services[$key] = $service;

            ServiceRate::firstOrCreate(
                ['service_id' => $service->id, 'package_id' => null, 'event_type_id' => null, 'valid_from' => self::VALID_FROM],
                ['currency_code' => 'PEN', 'amount' => $price, 'valid_until' => null],
            );

            foreach ($eventRates as [$packageKey, $eventName, $eventPrice]) {
                $eventTypeId = EventType::where('name', $eventName)->value('id');
                ServiceRate::firstOrCreate(
                    ['service_id' => $service->id, 'package_id' => $packages[$packageKey]->id, 'event_type_id' => $eventTypeId, 'valid_from' => self::VALID_FROM],
                    ['currency_code' => 'PEN', 'amount' => $eventPrice, 'valid_until' => null],
                );
            }

            foreach ($packageKeys as $packageKey) {
                $sort++;
                $replaceId = $replacesKey ? ($services[$replacesKey]->id ?? null) : null;
                $isOptional = is_array($optional) ? (bool) ($optional[$packageKey] ?? false) : $optional;
                PackageService::firstOrCreate(
                    ['package_id' => $packages[$packageKey]->id, 'service_id' => $service->id],
                    [
                        'default_quantity' => $initialQuantity ?? match ($quantityRule) {
                            'tables_of_10' => 10,
                            'per_person' => 100,
                            'per_adult' => 100,
                            'per_child' => 30,
                            'staff_per_50' => 4,
                            default => 1,
                        },
                        'is_optional' => $isOptional,
                        'quantity_rule' => $quantityRule,
                        'quantity_rule_value' => match ($quantityRule) {
                            'tables_of_10' => 10,
                            'staff_per_50' => 50,
                            default => null,
                        },
                        'event_type_names' => $eventNamesForItem ?: null,
                        'replaces_service_id' => $replaceId,
                        'sort_order' => $sort,
                        'display_quantity' => $displayQuantity,
                        'notes' => null,
                    ],
                );
            }

            return $service;
        };

        $all = ['basico', 'estandar', 'premium'];
        $higher = ['estandar', 'premium'];
        $nonCorporateEvents = array_values(array_diff($eventNames, ['Corporativo']));

        // Local y decoración del salón.
        $add('Alquiler Centro de Convenciones Raíces', 'LOCAL', 2500, 'día', $all);
        $add('Decoración Salón Principal — básica', 'DECORACION SALON', 1000, 'servicio', ['basico'], false, 'fixed', [], null, null, false);
        $add('Decoración Salón Principal — personalizada', 'DECORACION SALON', 2000, 'servicio', ['estandar', 'premium'], false, 'fixed', [], null, 'DECORACION SALON|Decoración Salón Principal — básica', false);
        $add('Decoración exclusiva', 'DECORACION SALON', 3000, 'servicio', ['premium'], false, 'fixed', [], null, 'DECORACION SALON|Decoración Salón Principal — personalizada', false);
        $add('Decoración sección de fotos', 'DECORACION SALON', 800, 'servicio', $higher, false, 'fixed', [], null, null, false);

        // Mobiliario, menajería y cristalería.
        $add('Mesas decoradas de 10 personas', 'MOBILIARIO', 20, 'mesa', $all, false, 'tables_of_10');
        $add('Sillas Tiffany', 'MOBILIARIO', 3, 'unidad', $all, false, 'per_person');
        $add('Mesas protocolares', 'MOBILIARIO', 20, 'unidad', $all, false, 'fixed', [], null, null, true, [], 2);
        $add('Sillas protocolares', 'MOBILIARIO', 3, 'unidad', $all, false, 'per_person');
        $add('Centro de mesa simple', 'MOBILIARIO', 15, 'mesa', $all, false, 'tables_of_10', ['Corporativo'], null, null, false);
        $add('Centro de mesa — flores artificiales', 'MOBILIARIO', 15, 'mesa', $higher, false, 'tables_of_10');
        $add('Centro de mesa — flores naturales', 'MOBILIARIO', 25, 'mesa', ['premium'], false, 'tables_of_10', [], null, 'MOBILIARIO|Centro de mesa — flores artificiales');
        foreach ([
            'Platos de sitio', 'Platos de fondo', 'Cuchillo y tenedor (juego)', 'Servilletas de tela',
        ] as $item) {
            $add($item, 'MENAJERIA', 1, 'unidad', $all, false, 'per_person');
        }
        foreach (['Copas de champagne', 'Vasos de whisky', 'Vasos de cerveza'] as $item) {
            $add($item, 'CRISTALERIA', 1, 'unidad', $all, false, 'per_person');
        }
        $add('Copas de vino', 'CRISTALERIA', 1, 'unidad', $higher, false, 'per_person');
        $add('Copas de cóctel', 'CRISTALERIA', 1, 'unidad', $higher, false, 'per_person');
        $add('Hielera por mesa con pinza', 'CRISTALERIA', 4, 'mesa', $higher, false, 'tables_of_10');
        $add('Jarras por mesa', 'CRISTALERIA', 4, 'mesa', $higher, false, 'tables_of_10');
        $add('Vasos de agua', 'CRISTALERIA', 1, 'unidad', ['basico'], false, 'per_person', ['Corporativo']);
        $add('Vasos de agua', 'CRISTALERIA', 1, 'unidad', $higher, false, 'per_person');

        // Bebidas, personal y equipos.
        $add('Coca Cola 2.5L', 'BEBIDAS', 10, 'botella', $all, false, 'tables_of_10', [], null, null, true,
            [['estandar', 'Corporativo', 9], ['premium', 'Corporativo', 9]]);
        $add('Agua mineral sin gas', 'BEBIDAS', 4, 'botella', $all, false, 'tables_of_10', [], null, null, true,
            [['estandar', 'Corporativo', 3.5], ['premium', 'Corporativo', 3.5]]);
        $add('Hielo (bolsas)', 'BEBIDAS', 8, 'bolsa', $all, false, 'fixed', [], null, null, true, [], 6);
        $add('Ron Flor de Caña 4 años', 'BEBIDAS', 37, 'botella', $all, false, 'tables_of_10');
        $add('Champagne Tabernero', 'BEBIDAS', 15, 'botella', $higher, false, 'fixed', [], null, null, true, [], 4);
        $add('Vino Gran Borgoña', 'BEBIDAS', 15, 'botella', $higher, false, 'fixed', ['Corporativo'], null, null, true, [], 3);
        $add('Ginger bebida con gas', 'BEBIDAS', 15, 'botella', $higher, false, 'tables_of_10', [
            'Quinceañera', 'Graduación colegio inicial/primaria', 'Graduación colegio secundaria', 'Baby Shower', 'Fiesta Infantil', 'Reunión familiar',
        ]);
        $staffQty = ['basico' => true, 'estandar' => false, 'premium' => false];
        $add('Mozos', 'PERSONAL DE SERVICIO', 80, 'persona', $all, $staffQty, 'staff_per_50');
        $add('Personal de seguridad', 'PERSONAL DE SERVICIO', 100, 'persona', $all, $staffQty, 'fixed');
        $add('Personal de limpieza', 'PERSONAL DE SERVICIO', 80, 'persona', $all, $staffQty, 'staff_per_50');
        $add('Barman', 'PERSONAL DE SERVICIO', 150, 'servicio', $higher, false, 'fixed', [], null, null, false);
        $add('Maestro de ceremonias', 'PERSONAL DE SERVICIO', 300, 'servicio', $higher, false, 'fixed', $nonCorporateEvents, null, null, false);
        $add('Maestro de ceremonias / moderador', 'PERSONAL DE SERVICIO', 300, 'servicio', $higher, false, 'fixed', ['Corporativo'], null, null, false);
        $add('Jóvenes de guardia', 'PERSONAL DE SERVICIO', 600, 'servicio', ['estandar'], true, 'fixed', [
            'Quinceañera', 'Matrimonio', 'Graduación colegio inicial/primaria', 'Graduación colegio secundaria',
        ], null, null, false);
        // El precio Premium se guarda como tarifa por paquete y tipo de evento.
        $youth = $services['PERSONAL DE SERVICIO|Jóvenes de guardia'];
        ServiceRate::firstOrCreate(
            ['service_id' => $youth->id, 'package_id' => $packages['premium']->id, 'event_type_id' => null, 'valid_from' => self::VALID_FROM],
            ['currency_code' => 'PEN', 'amount' => 900, 'valid_until' => null],
        );
        PackageService::firstOrCreate(
            ['package_id' => $packages['premium']->id, 'service_id' => $youth->id],
            ['default_quantity' => 1, 'is_optional' => false, 'quantity_rule' => 'fixed', 'event_type_names' => [
                'Quinceañera', 'Matrimonio', 'Graduación colegio inicial/primaria', 'Graduación colegio secundaria',
            ], 'sort_order' => ++$sort, 'display_quantity' => false],
        );
        $add('Equipo de sonido básico', 'OTROS SERVICIOS', 1500, 'servicio', ['basico'], true, 'fixed', [], null, null, false);
        $add('Equipo de sonido estándar', 'OTROS SERVICIOS', 3800, 'servicio', $higher, false, 'fixed', [], null, 'OTROS SERVICIOS|Equipo de sonido básico', false);
        $add('Equipo de sonido premium', 'OTROS SERVICIOS', 6500, 'servicio', ['premium'], false, 'fixed', [], null, 'OTROS SERVICIOS|Equipo de sonido estándar', false);
        $add('Proyector + pantalla', 'OTROS SERVICIOS', 1000, 'servicio', $higher, false, 'fixed', ['Corporativo'], null, null, false);

        // Ceremonia civil, bocaditos y extras de los paquetes.
        $add('Carpa con telas decorada', 'DECORACION CEREMONIA BODA CIVIL', 700, 'servicio', $higher, false, 'fixed', ['Matrimonio'], null, null, false);
        $add('Arcos con flores', 'DECORACION CEREMONIA BODA CIVIL', 80, 'unidad', $higher, false, 'fixed', ['Matrimonio'], null, null, true, [], 3);
        $add('Sillas invitados ceremonia área verde', 'DECORACION CEREMONIA BODA CIVIL', 3, 'unidad', $higher, false, 'fixed', ['Matrimonio'], null, null, true, [], 70);
        $add('Sillas testigos', 'DECORACION CEREMONIA BODA CIVIL', 10, 'unidad', $higher, false, 'fixed', ['Matrimonio'], null, null, true, [], 4);
        $add('Sillas novios', 'DECORACION CEREMONIA BODA CIVIL', 25, 'unidad', $higher, false, 'fixed', ['Matrimonio'], null, null, true, [], 2);
        $add('Mesa de apoyo común', 'DECORACION CEREMONIA BODA CIVIL', 10, 'servicio', $higher, false, 'fixed', ['Matrimonio'], null, null, false);
        $add('Mesa de apoyo civil (con mantelería)', 'DECORACION CEREMONIA BODA CIVIL', 25, 'servicio', $higher, false, 'fixed', ['Matrimonio'], null, null, false);
        $add('Bocaditos de bienvenida', 'EXTRAS DEL PAQUETE', 150, 'servicio', $higher, false, 'fixed', ['Corporativo'], null, null, false);
        $add('Cóctel de cierre', 'EXTRAS DEL PAQUETE', 150, 'servicio', $higher, false, 'fixed', ['Corporativo'], null, null, false);
        $add('Torta decorada', 'EXTRAS DEL PAQUETE', 1000, 'servicio', ['estandar'], true, 'fixed', $nonCorporateEvents, null, null, false);
        $cake = $add('Torta decorada', 'EXTRAS DEL PAQUETE', 1000, 'servicio', ['premium'], false, 'fixed', [], null, null, false);
        ServiceRate::firstOrCreate(
            ['service_id' => $cake->id, 'package_id' => $packages['premium']->id, 'event_type_id' => null, 'valid_from' => self::VALID_FROM],
            ['currency_code' => 'PEN', 'amount' => 1500, 'valid_until' => null],
        );
        $add('Bocaditos salados y dulces', 'EXTRAS DEL PAQUETE', 150, 'bandeja', ['estandar'], true, 'fixed', $nonCorporateEvents, null, null, true, [], 2);
        $add('Bocaditos salados y dulces', 'EXTRAS DEL PAQUETE', 150, 'bandeja', ['premium'], false, 'fixed', [], null, null, true, [], 2);
        $add('Fotografía y filmación', 'OTROS SERVICIOS', 1000, 'servicio', ['estandar'], true, 'fixed', [], null, null, false);
        $photo = $add('Fotografía y filmación', 'OTROS SERVICIOS', 1000, 'servicio', ['premium'], false, 'fixed', [], null, null, false);
        ServiceRate::firstOrCreate(
            ['service_id' => $photo->id, 'package_id' => $packages['premium']->id, 'event_type_id' => null, 'valid_from' => self::VALID_FROM],
            ['currency_code' => 'PEN', 'amount' => 2000, 'valid_until' => null],
        );
        $add('Hora loca', 'OTROS SERVICIOS', 500, 'servicio', ['estandar'], true, 'fixed', [], null, null, false);
        $partyTime = $add('Hora loca', 'OTROS SERVICIOS', 500, 'servicio', ['premium'], false, 'fixed', [], null, null, false);
        ServiceRate::firstOrCreate(
            ['service_id' => $partyTime->id, 'package_id' => $packages['premium']->id, 'event_type_id' => null, 'valid_from' => self::VALID_FROM],
            ['currency_code' => 'PEN', 'amount' => 800, 'valid_until' => null],
        );
        $add('Fotos 360', 'OTROS SERVICIOS', 500, 'servicio', ['premium'], true, 'fixed', [], null, null, false);
        $add('Chisperos', 'OTROS SERVICIOS', 300, 'servicio', ['premium'], true, 'fixed', [], null, null, false);
        $drone = $add('Dron (fotografía aérea)', 'OTROS SERVICIOS', 0, 'servicio', ['premium'], true, 'fixed', [], null, null, false);
        $drone->description ??= 'Cotizar aparte; el S/ 0 del generador no es una tarifa de venta.';
        $drone->save();
        PackageService::where('package_id', $packages['premium']->id)->where('service_id', $drone->id)
            ->update(['notes' => 'Cotizar aparte; confirmar el precio antes de enviar la cotización.']);
        $add('Grupo musical en vivo', 'OTROS SERVICIOS', 4000, 'servicio', ['premium'], true, 'fixed', [], null, null, false);

        // Alternativas de menú; la selección se registra en líneas de la versión de cotización.
        $menus = [
            ['Entrada', 'Entrada adultos', 'pers.', 8, ['Causa de pollo', 'Rocoto relleno', 'Papa a la ocopa', 'Papa rellena', 'Brochetas de pollo', 'Botellitas broster'], 'adult_entry'],
            ['Plato de fondo', 'Fondo adultos', 'pers.', 30, ['Panceta de cerdo + tamal + moraya', 'Chiriuchu Raíces', 'Cuy al horno con pastel tallarín + papa', 'Lechón al horno + papas y camote', 'Asado de ternera + puré de papas', 'Trucha brosther + papas doradas'], 'adult_main'],
            ['Postre', 'Postre adultos', 'pers.', 8, ['Mousse de fresa', 'Cheesecake de maracuyá', 'Carlota de vainilla', 'Tiramisú', 'Torta helada', 'Torta 3 leches'], 'adult_dessert'],
            ['Bebidas adultos', 'Bebida caliente/fría', 'pers.', 3, ['Muña', 'Anís', 'Manzanilla', 'Pisco Sour de cortesía', 'Limonada natural', 'Limonada brasileña', 'Piña colada', 'Limonada frutos rojos', 'Limonada de frutos', 'Jugos naturales variados'], 'adult_beverage'],
            ['Bocaditos infantiles', 'Menú niños', 'niño', 10, ['Pizzetas', 'Botellitas broster', 'Brochetas de pollo con verduras', 'Alitas a la BBQ', 'Conitos de pollo broster'], 'child_food'],
            ['Bebidas infantiles', 'Bebidas sin alcohol', 'niño', 4, ['Jugo de fruta natural', 'Limonada', 'Agua con gas', 'Gaseosa personal', 'Cóctel de frutas sin alcohol'], 'child_beverage'],
        ];
        $menuPrices = [
            'Bebidas adultos' => [3, 3, 3, 3, 3, 5, 5, 5, 5, 4],
            'Menú niños' => [10, 8, 8, 12, 10],
            'Bebidas sin alcohol' => [4, 3, 3, 4, 5],
        ];
        $childEvents = ['Baby Shower', 'Fiesta Infantil', 'Graduación colegio inicial/primaria', 'Reunión familiar'];
        foreach ($menus as [$category, $label, $unit, $defaultPrice, $options, $group]) {
            foreach ($options as $index => $item) {
                $price = $menuPrices[$label][$index] ?? $defaultPrice;
                $menuRule = str_starts_with($group, 'child_') ? 'per_child' : 'per_adult';
                $add($item, $category, $price, $unit, $all, true, $menuRule,
                    str_starts_with($group, 'child_') ? $childEvents : [], $group);
            }
        }
    }
}
