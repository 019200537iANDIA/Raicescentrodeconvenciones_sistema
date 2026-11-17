# Raíces — Diccionario de datos v1

**Propósito:** especificar la base de datos MySQL que implementará Laravel. Se basa en los instrumentos v2 y las decisiones confirmadas posteriormente. Es diseño; aún no se ha desplegado una base ni se han cargado clientes o precios reales.

## Reglas que condicionan el esquema

- Máximo de una reserva activa por fecha en la primera versión. Una ampliación futura podrá cambiar la política para permitir eventos simultáneos.
- La cotización vence a los 15 días. Si el cliente pidió separar/reservar y no paga el adelanto dentro del plazo, el evento tentativo se libera.
- El adelanto acordado debe estar entre 10 % y 30 % del total. Se guarda el porcentaje y monto exactos de cada versión de cotización.
- Paquetes comerciales: básico, estándar y premium. Los precios finales dependen de servicios y personalizaciones; no se codifican precios fijos ficticios.
- La cotización puede originar varias versiones. Los cambios guardan una nueva versión y no sobrescriben lo enviado/aceptado.
- Se reemplaza el generador HTML. La importación de cotizaciones históricas desde Drive/Excel aún requiere plan y depuración de duplicados.
- Los datos de clientes incluyen nombre, celular, DNI y dirección. El DNI y la dirección se almacenarán cifrados desde Laravel; el distrito se conserva como dato separado para reglas de residencia.
- Para el cliente, el documento muestra servicios y cantidades, además del total y precio por persona cuando aplique; oculta el precio unitario de cada producto.
- La v1 permite asignar personal a un evento, pero no gestiona turnos, reemplazos, horas ni pagos del personal. Esas funciones quedan para una futura versión.
- Propietario y gerente pueden eliminar registros según la matriz del Documento 3. La solución debe usar eliminación lógica cuando corresponda y registrar usuario, fecha y motivo en auditoría.

## Entidades principales

| Área | Tablas | Uso |
|---|---|---|
| Acceso | `users` | Propietario y gerente, autenticación y rol. |
| Clientes | `clients` | Identidad, contacto, residencia y búsqueda de clientes recurrentes. |
| Captación | `event_types`, `inquiries`, `interactions` | Tipo de evento, canal, consultas, contactos, respuesta, próxima acción y recordatorios. |
| Catálogo | `services`, `packages`, `package_services`, `service_rates` | Componentes comerciales y tarifas con vigencia histórica. |
| Cotizaciones | `quotes`, `quote_versions`, `quote_lines`, `quote_adjustments` | Alternativas, versiones inmutables, detalle interno y ajustes autorizados. |
| Precios | `pricing_rules`, `system_settings` | Criterios configurables por día, horario, volumen, temporada, residencia y promociones. |
| Ambientes | `venues`, `venue_configurations`, `venue_configuration_items` | Ambientes individuales y combinaciones de uso. |
| Reservas/eventos | `events` | Reserva directa o derivada de una cotización; retención, adelanto, estado y fecha. |
| Contrato y pagos | `contracts`, `installments`, `payments`, `refunds` | Versiones contractuales, vencimientos, comprobantes, saldos y devoluciones. |
| Preparación | `suppliers`, `supplier_assignments`, `staff`, `staff_assignments`, `event_tasks` | Proveedores y personal por evento, pedidos, especificaciones, responsables y fechas límite. |
| Control | `audit_logs` | Historial de altas, cambios, anulaciones, precios, estados y usuario responsable. |

## Estados y comportamiento

1. Una consulta se registra en `inquiries`; cada contacto o seguimiento se guarda como una fila de `interactions`.
2. Una oportunidad puede tener cotizaciones alternativas relacionadas mediante `parent_quote_id` y varias versiones numeradas.
3. Cada `quote_version` congela moneda, totales, porcentaje/monto de adelanto, fecha límite y archivo emitido. Cada `quote_line` conserva una descripción histórica para que editar el catálogo no altere cotizaciones anteriores.
4. Si el cliente solicita reservar, se crea un `event` en estado `pending_advance` con fecha límite de retención. La fecha activa es única. Al registrar adelanto, el evento pasa a `reserved`; si el plazo vence sin adelanto, pasa a `released` y deja libre la fecha.
5. El índice único de `active_event_date` excluye eventos `cancelled`, `released` y `completed`; así preserva historial y evita dos eventos activos el mismo día en v1.
6. Una reserva directa crea un evento sin `quote_id` ni `quote_version_id`; puede completarse con una cotización y contrato posteriores.
7. Un cambio de alcance genera otra versión de cotización/contrato y un registro de auditoría. No se eliminan los documentos previos.
8. Cancelar no borra pagos: crea una solicitud de devolución asociada al evento y, si aplica, al pago de origen.

## Valores todavía por definir al implementar

- Qué rol puede autorizar excepciones al adelanto normal de 10–30 %.
- Edades que distinguen tarifa de adulto y niño; métodos de pago reales; moneda y comprobantes aceptados.
- Reglas exactas y compatibilidad de descuentos, horarios, temporadas y precios por número de asistentes.
- Fechas de cuota, condiciones de devolución, periodo de retención cuando hay negociación y cómo se cuentan los 15 días.
- Política de conservación/eliminación segura del DNI y dirección.
- Catálogo final de ambientes, capacidades y combinaciones; en v1 la regla sigue siendo una reserva por fecha.
- Importación, deduplicación y validación de registros del generador HTML, Drive y Excel.

## Fase posterior

El esquema de v1 no contiene tablas de inventario, movimientos de bienes, galería fotográfica, turnos detallados, horas, reemplazos ni pagos/adelantos del personal. Se incorporarán solo con alcance aprobado.
