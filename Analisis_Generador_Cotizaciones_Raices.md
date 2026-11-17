# Revisión del generador de cotizaciones de Raíces

**Fuente revisada:** `htmlraicesnuevo18_10_26.zip`, que contiene `htmlraicesnuevo18_10_26/index.html` (generador fechado por el nombre del archivo: 18/10/2026).

## Datos que sí contiene

- Doce tipos de evento: Bautizo, Quinceañera, Matrimonio, Graduación Universitaria, Baby Shower, Corporativo, Graduación colegio inicial/primaria, Graduación colegio secundaria, Fiesta Infantil, Reunión familiar, Cumpleaños y Aniversario.
- Tres paquetes: Básico, Estándar y Premium.
- Un catálogo amplio con categorías, precios en soles, unidades, cantidades, servicios incluidos y opcionales, sustituciones entre paquetes y variaciones según tipo de evento.
- Cantidades calculadas desde asistentes: mesas como `ceil(personas / 10)`; mozos y limpieza como `max(2, ceil(personas / 50) * 2)`.
- Menús con alternativas para entrada, fondo, postre, bebidas y opciones infantiles. Entrada y fondo son selección única; postres y bebidas admiten varias selecciones.
- Distribución adulto/niño para Baby Shower, Fiesta Infantil, Graduación colegio inicial/primaria y Reunión familiar. El generador también asigna inicialmente 70/30 al cambiar el aforo total; no asumimos que esa proporción sea una política aprobada.
- Edición manual de cantidad y precio, selección de opcionales, sustitución de servicios y ajuste del precio por persona.
- Cotización de cliente y vista interna para gerente. La vista interna muestra precios unitarios; la del cliente muestra conceptos/cantidades y los totales, además del precio por persona cuando se habilita.
- Vigencia de 15 días y generación de PDFs.

## Catálogo y precios extraídos

Los importes siguientes son los valores codificados en el HTML. El código calcula y redondea los importes de línea a soles enteros, aunque algunas tarifas unitarias contienen céntimos.

### Base del paquete Básico

| Categoría | Servicio o producto | Tarifa | Cantidad o regla |
|---|---|---:|---|
| Local | Alquiler Centro de Convenciones Raíces | S/ 2,500 | 1 día; el PDF describe 8 horas |
| Decoración | Decoración Salón Principal — básica | S/ 1,000 | 1 |
| Mobiliario | Mesas decoradas de 10 personas | S/ 20 | ceil(personas / 10) |
| Mobiliario | Sillas Tiffany | S/ 3 | por persona |
| Mobiliario | Mesas protocolares | S/ 20 | 2 |
| Mobiliario | Sillas protocolares | S/ 3 | por persona |
| Menajería | Platos de sitio; platos de fondo; juego de cuchillo y tenedor; servilletas de tela | S/ 1 cada uno | por persona y producto |
| Cristalería | Copas de champagne; vasos de whisky; vasos de cerveza | S/ 1 cada uno | por persona y producto |
| Bebidas | Coca Cola 2.5L | S/ 10 | ceil(personas / 10) |
| Bebidas | Agua mineral sin gas | S/ 4 | ceil(personas / 10) |
| Bebidas | Hielo (bolsas) | S/ 8 | 6 |
| Bebidas | Ron Flor de Caña 4 años | S/ 37 | ceil(personas / 10) |
| Personal de servicio | Mozos | S/ 80 | opcional; max(2, ceil(personas / 50) * 2) |
| Personal de servicio | Personal de seguridad | S/ 100 | opcional; 1 |
| Personal de servicio | Personal de limpieza | S/ 80 | opcional; max(2, ceil(personas / 50) * 2) |
| Otros servicios | Equipo de sonido básico | S/ 1,500 | opcional; 1 |

En Corporativo se añaden centro de mesa simple (S/ 15 por mesa, incluido por la fórmula del generador) y vasos de agua (S/ 1 por persona). Gastos administrativos comienzan en S/ 0 y solo se usan como ajuste manual.

### Adiciones y cambios de Estándar

- Sustituye decoración básica por decoración personalizada (S/ 2,000); añade decoración de sección de fotos (S/ 800), centros de mesa artificiales (S/ 15 por mesa), copas de vino (S/ 1/persona), copas de cóctel (S/ 1/persona), hielera con pinza y jarras (S/ 4 cada una por mesa).
- Incluye mozos, seguridad y limpieza; añade barman (S/ 150) y maestro de ceremonias (S/ 300; en Corporativo, moderador).
- Añade bebidas y sonido estándar (S/ 3,800). Para eventos no corporativos, Coca Cola (S/ 10), agua (S/ 4), hielo (S/ 8 × 6), champagne Tabernero (S/ 15 × 4) y ron (S/ 37 por mesa) reemplazan/refuerzan la lista Básica. En Corporativo, vino Gran Borgoña (S/ 15 × 3), Coca Cola (S/ 9 por mesa), agua (S/ 3.50 por mesa), hielo, champagne y ron.
- Ginger (S/ 15 por mesa) se agrega para Quinceañera, graduaciones escolares, Baby Shower, Fiesta Infantil y Reunión familiar.
- Corporativo añade bocaditos de bienvenida y cóctel de cierre (S/ 150 cada uno), y proyector más pantalla (S/ 1,000).
- Matrimonio añade carpa decorada (S/ 700), arcos con flores (S/ 80 × 3), sillas de invitados (S/ 3 × 70), sillas de testigos (S/ 10 × 4), sillas de novios (S/ 25 × 2) y mesas de apoyo (S/ 10 y S/ 25).
- Opcionales no corporativos: torta decorada (S/ 1,000), dos bandejas de bocaditos (S/ 150 por bandeja), fotografía y filmación (S/ 1,000) y hora loca (S/ 500). Quinceañeras y graduaciones pueden añadir jóvenes de guardia (S/ 600).

### Adiciones y cambios de Premium

- Sustituye decoración personalizada por decoración exclusiva (S/ 3,000), centros artificiales por naturales (S/ 25 por mesa) y sonido estándar por premium (S/ 6,500).
- Incluye torta decorada (S/ 1,500), dos bandejas de bocaditos (S/ 150 por bandeja), hora loca (S/ 800) y fotografía y filmación (S/ 2,000). Para Quinceañera y graduaciones incluye jóvenes de guardia (S/ 900).
- Opcionales: fotos 360 (S/ 500), chisperos (S/ 300), dron (sin precio; “cotizar aparte”) y grupo musical en vivo (S/ 4,000).

### Menús

| Grupo | Alternativas | Tarifa unitaria |
|---|---|---|
| Entrada adultos | Causa de pollo; Rocoto relleno; Papa a la ocopa; Papa rellena; Brochetas de pollo; Botellitas broster | S/ 8 por persona |
| Fondo adultos | Panceta de cerdo + tamal + moraya; Chiriuchu Raíces; Cuy al horno + pastel tallarín + papa; Lechón al horno + papas y camote; Asado de ternera + puré; Trucha brosther + papas doradas | S/ 30 por persona |
| Postre adultos | Mousse de fresa; Cheesecake de maracuyá; Carlota de vainilla; Tiramisú; Torta helada; Torta 3 leches | S/ 8 por persona |
| Bebidas adultos | Muña, anís, manzanilla, pisco sour de cortesía, limonada natural, limonada brasileña, piña colada, limonada frutos rojos, limonada de frutos, jugos naturales variados | S/ 3, 3, 3, 3, 3, 5, 5, 5, 5 y 4 por persona, en ese orden |
| Bocaditos infantiles | Pizzetas; Botellitas broster; Brochetas de pollo con verduras; Alitas a la BBQ; Conitos de pollo broster | S/ 10, 8, 8, 12 y 10 por niño |
| Bebidas infantiles | Jugo de fruta natural; Limonada; Agua con gas; Gaseosa personal; Cóctel de frutas sin alcohol | S/ 4, 3, 3, 4 y 5 por niño |

El sistema calcula el costo común entre asistentes y suma por separado el menú adulto y el infantil. La gerente puede editar el precio por persona; el generador compensa la diferencia en gastos administrativos o alquiler. Esa conducta debe trasladarse como ajuste autorizado y auditable, no como edición silenciosa.

## Diferencias frente a las reglas confirmadas para la tesis

| Tema | HTML anterior | Regla confirmada para el sistema actual |
|---|---|---|
| Adelanto | Valor inicial de 50% | Aceptar de 10% a 30%; prevalece la regla reciente |
| Vigencia de cotización | 15 días calendario | Coincide: 15 días |
| Reserva de fecha | Calendario consulta historial externo | Una reserva activa por fecha en V1; liberar si no se paga adelanto dentro del plazo acordado |
| Atención del local | 8 horas; hora adicional S/ 220 | Horas y turnos quedan para una versión posterior |
| Distribución adulto/niño | Solo ciertos eventos; autocompleta 70/30 al cambiar asistentes | Registrar cantidades reales; la proporción automática necesita aprobación expresa |
| Datos del cliente | Nombre y teléfono | La versión nueva también guarda DNI y dirección, cifrados |
| Promoción entre semana | No hay descuento aplicado en el cálculo. `menuXp` contiene porcentajes distintos por tipo de evento, pero no se usa en otra parte del archivo | Se conoce una promoción de lunes a jueves de 20–30%; faltan el porcentaje exacto, su base y condiciones. No activarla como regla fija aún |

## Conclusión para el desarrollo

El HTML aporta lo necesario para cargar un primer catálogo, los tres paquetes, precios, cantidades iniciales, sustituciones y opciones de menú. La aplicación nueva debe conservar esos datos como tarifas editables con vigencia, y guardar una copia de cada concepto/precio dentro de cada versión de cotización. Las reglas recientes dadas por el usuario prevalecen cuando contradicen el generador. La hoja HTML no basta para aprobar la promoción entre semana ni excepciones al adelanto; esas políticas deben seguir configurables o pendientes, sin inventar valores.

El almacenamiento del HTML en Google Apps Script, Sheets y Drive es parte de la implementación antigua y debe sustituirse por Laravel/MySQL y documentos generados por el sistema.
