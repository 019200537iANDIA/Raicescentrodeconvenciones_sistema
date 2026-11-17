const fs = require('fs');
const path = require('path');
const out = path.join(process.cwd(), 'Diagramas_Raices');
const xml = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
function box(id, title, fields, x,y,w=250,h=150, style='rounded=0;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacing=7;fillColor=#f8fbf8;strokeColor=#2c5f4a;fontSize=11;') {
 const val=`<b>${title}</b><br>${fields.join('<br>')}`;
 return `<mxCell id="${id}" value="${xml(val)}" style="${style}" vertex="1" parent="1"><mxGeometry x="${x}" y="${y}" width="${w}" height="${h}" as="geometry"/></mxCell>`;
}
function rel(id,a,b,label){return `<mxCell id="${id}" value="${xml(label)}" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;endArrow=ERoneToMany;startArrow=ERzeroToMany;startFill=0;endFill=0;strokeColor=#526b60;fontSize=10;" edge="1" parent="1" source="${a}" target="${b}"><mxGeometry relative="1" as="geometry"/></mxCell>`;}
function note(id,value,x,y,w,h){return box(id,'NOTA', [value],x,y,w,h,'shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=11;');}
function save(file,title,cells,width=1750,height=1120){
 const body=`<mxfile host="app.diagrams.net" modified="2026-11-15T00:00:00.000Z" agent="Codex" version="24.7.17"><diagram id="${file}" name="${xml(title)}"><mxGraphModel dx="${width}" dy="${height}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="${width}" pageHeight="${height}" math="0" shadow="0"><root><mxCell id="0"/><mxCell id="1" parent="0"/>${cells}</root></mxGraphModel></diagram></mxfile>`;
 fs.writeFileSync(path.join(out,file),body,'utf8');
}

// View 1: customer history, quote alternatives/versions and direct-created events.
let a='';
a+=box('c_title','MODELO LÓGICO · COMERCIAL Y COTIZACIONES',['V2 · Separar oportunidad, cotización, versiones y evento/reserva'],380,25,950,55,'text;html=1;align=center;verticalAlign=middle;fontSize=19;fontStyle=1;fontColor=#2c5f4a;');
a+=box('c_client','CLIENTE',['PK cliente_id','nombre_completo · celular (búsqueda repetidos)','DNI · dirección · distrito/residencia','uso: validar identidad y residencia/descuento','acceso restringido; retención por definir','tipo_cliente: nuevo / recurrente'],45,120,270,190);
a+=box('c_lead','CONSULTA / OPORTUNIDAD',['PK consulta_id · FK cliente_id','canal · fecha · tipo_evento_id','resumen de necesidad / presupuesto','estado · responsable_id'],390,120,270,165);
a+=box('c_touch','INTERACCIÓN / SEGUIMIENTO',['PK interaccion_id · FK consulta_id','usuario_id · fecha · canal','resultado: pendiente/acepta/rechaza','próxima acción / fecha recordatorio','ultimátum y respuesta registrada'],735,120,280,180);
a+=box('c_quote','COTIZACIÓN',['PK cotizacion_id · FK consulta_id','cotizacion_padre_id (alternativas)','fecha_evento · adultos · niños','ambiente_config_id · total','vigencia: 15 días','estado / creado_por'],390,405,270,175);
a+=box('c_version','VERSIÓN DE COTIZACIÓN',['PK version_id · FK cotizacion_id','número · fecha · autor','motivo / aceptación del cliente','subtotal · descuento · total congelados','PDF/contrato emitido y hash/ruta'],735,405,280,170);
a+=box('c_line','LÍNEA DE VERSIÓN',['PK linea_id · FK version_id','FK servicio_id','descripción_snapshot · cantidad','precio interno · subtotal · descuento','mostrar cantidad; ocultar precio unitario'],1090,405,285,175);
a+=box('c_service','SERVICIO / PAQUETE',['PK servicio_id · nombre comercial','paquetes: básico / estándar / premium','componentes del paquete por versión','precio no fijo: composición personalizada','categoría / descripción / activo'],1090,120,300,190);
a+=box('c_type','TIPO DE EVENTO',['PK tipo_evento_id · nombre','matrimonio · 15 años · cumpleaños','graduación/promoción · corporativo · otro'],45,405,270,145);
a+=box('c_event','EVENTO / RESERVA',['PK evento_id · FK cliente_id','FK cotizacion_id/version_id nullable (reserva directa)','FK tipo_evento_id · fecha/hora','estado operativo / contrato vigente','nace de cotización o reserva directa'],1450,405,270,180);
a+=rel('cr1','c_client','c_lead','1 : N');a+=rel('cr2','c_lead','c_touch','1 : N');a+=rel('cr3','c_lead','c_quote','1 : N');a+=rel('cr4','c_quote','c_version','1 : N');a+=rel('cr5','c_version','c_line','1 : N');a+=rel('cr6','c_service','c_line','1 : N');a+=rel('cr7','c_client','c_event','1 : N');a+=rel('cr8','c_version','c_event','0..1 : 0..1');a+=rel('cr9','c_type','c_lead','1 : N');a+=rel('cr10','c_type','c_event','1 : N');
a+=note('c_note','El cliente puede volver por la misma cotización con otro paquete: crear alternativa o nueva versión vinculada; conservar versiones anteriores para historial. No sobrescribir precios/servicios ya enviados. Reserva directa permite evento sin cotización.',45,700,790,100);
a+=note('c_note2','Los precios unitarios se guardan para cálculo interno. El PDF para cliente muestra descripción, cantidades y total/por persona según regla aprobada, pero no el precio por producto.',875,700,790,100);
save('Modelo_de_datos_Raices.drawio','Modelo de datos · comercial',a,1750,900);

// View 2: configurable pricing and venue-level availability constraints.
let b='';
b+=box('p_title','MODELO LÓGICO · TARIFAS, DESCUENTOS Y AMBIENTES',['Políticas configurables, con vigencia y autorización'],390,25,950,55,'text;html=1;align=center;verticalAlign=middle;fontSize=19;fontStyle=1;fontColor=#2c5f4a;');
b+=box('p_service','SERVICIO / PAQUETE',['PK servicio_id · categoría','componentes / unidad de medida','activo · nombre comercial'],45,120,245,135);
b+=box('p_price','TARIFA BASE HISTÓRICA',['PK tarifa_id · FK servicio_id','precio · moneda por confirmar','válida_desde / hasta · autor','no asumir precio fijo por paquete'],365,120,265,155);
b+=box('p_rule','REGLA DE TARIFA',['PK regla_id · tipo y prioridad','temporada (altas/bajas por definir)','día de semana / horario día-noche','rango de asistentes · adultos/niños','residencia/distrito · promoción L–J 20–30 % (confirmar)','vigencia · activa · condiciones'],705,120,295,220);
b+=box('p_adj','AJUSTE / DESCUENTO APLICADO',['PK ajuste_id · FK version_id','FK regla_id nullable · monto/%','motivo conversado y cálculo resultante','autorizado_por (propietario/gerente)','fecha · compatibilidad con otras reglas'],1080,120,300,185);
b+=box('p_param','PARÁMETRO DE NEGOCIO',['PK parámetro_id · clave/valor','cotización: 15 días','adelanto permitido: 10–30 %','liberar fecha al vencer sin adelanto','un evento por fecha en la v1','historial: autor, vigencia y aprobador'],1450,120,270,205);
b+=box('p_venue','AMBIENTE / RECURSO',['PK ambiente_id · nombre','área verde · salón principal','mezanine · salón divisible (segmentos)','capacidad y acceso/restricciones'],45,430,245,155);
b+=box('p_combo','CONFIGURACIÓN DE AMBIENTES',['PK config_id · nombre','ambientes incluidos (tabla puente)','área verde + salón completo','área verde + salón + mezanine','área verde + medio salón / área verde sola','validar compatibilidad y capacidad'],365,430,265,195);
b+=box('p_policy','POLÍTICA DE DISPONIBILIDAD',['PK policy_id · vigencia del modo','V1: máximo 1 reserva por fecha','bloqueo completo de la fecha reservada','salón divisible no habilita 2 eventos en v1','escalamiento futuro requiere nueva política','ambientes elegidos se conservan por evento'],705,430,295,195);
b+=box('p_slot','BLOQUEO / RESERVA DE RECURSO',['PK bloqueo_id · FK evento_id','FK config_id / ambiente_id','fecha / estado disponible o reservada','adelanto acordado: 10–30 %','sin adelanto al día 15: liberar fecha','bloqueado_desde / vence / liberado_en'],1080,430,300,195);
b+=box('p_adults','CANTIDAD / PRECIO POR PERSONA',['por versión: adultos y niños separados','tabla de tarifa por grupo etario (definir edades)','regla para costo por cantidad de asistentes','total y precio promedio/persona'],1450,430,270,160);
b+=rel('pr1','p_service','p_price','1 : N');b+=rel('pr2','p_service','p_rule','aplica a');b+=rel('pr3','p_rule','p_adj','0 : N');b+=rel('pr4','p_venue','p_combo','N : N');b+=rel('pr5','p_combo','p_slot','1 : N');b+=rel('pr6','p_policy','p_slot','controla');
b+=note('p_note','Regla confirmada por el investigador: adelanto entre 10 % y 30 % para captar clientes en esta etapa. Guardar el importe acordado en cada cotización. El permiso para autorizar una excepción fuera de ese rango aún no está definido.',45,760,790,105);
b+=note('p_note2','Regla confirmada: cotización válida por 15 días; si no se paga el adelanto en ese plazo, la oportunidad vence y la fecha se libera. V1 permite una reserva por fecha; eventos simultáneos al dividir ambientes quedan para una futura ampliación.',875,760,790,105);
save('Modelo_datos_Raices_02_Tarifas_Ambientes.drawio','Modelo de datos · tarifas y ambientes',b,1750,910);

// View 3: event execution, supplier/staff assignments, contract and financial ledger.
let d='';
d+=box('e_title','MODELO LÓGICO · EJECUCIÓN, PAGOS Y TRAZABILIDAD',['Una reserva se transforma en un evento operativo con responsabilidades y documentos'],390,25,1000,55,'text;html=1;align=center;verticalAlign=middle;fontSize=19;fontStyle=1;fontColor=#2c5f4a;');
d+=box('e_event','EVENTO / RESERVA',['PK evento_id · FK cliente_id','fecha/hora · tipo · ambiente_config_id','estado / origen: cotización o directo','contrato vigente / notas operativas'],45,120,270,165);
d+=box('e_contract','CONTRATO / VERSIÓN',['PK contrato_id · FK evento_id','número de versión · fecha · estado','PDF/ruta · total y condiciones snapshot','cambios aprobados y firma'],390,120,280,165);
d+=box('e_pay','PAGO / MOVIMIENTO',['PK pago_id · FK evento_id','monto · fecha · método','tipo: adelanto/cuota/saldo/devolución','comprobante/ruta · registrado_por','anulado o reversado, sin borrar historia'],745,120,290,180);
d+=box('e_install','CALENDARIO DE CUOTAS',['PK cuota_id · FK evento_id','fecha límite · monto acordado','estado · pagado_en · pago_id','renegociación / nota'],1110,120,275,155);
d+=box('e_supplier','PROVEEDOR',['PK proveedor_id · nombre','tipo de producto/servicio','contacto · activo'],45,410,270,135);
d+=box('e_supplier_assign','ASIGNACIÓN DE PROVEEDOR',['PK asignación_id · FK evento_id/proveedor_id','pedido/especificación (color, piezas, etc.)','cantidad · fecha límite de pedido','horario de entrega/trabajo · cambio/autor','estado y responsable interno'],390,410,280,190);
d+=box('e_staff','PERSONAL',['PK personal_id · nombre','rol: mozo/seguridad/limpieza/MC/otro','contacto · activo'],745,410,290,135);
d+=box('e_staff_assign','ASIGNACIÓN DE PERSONAL',['PK asignación_id · FK evento_id/personal_id','personal asignado y función del evento','sin turnos/horas/pagos en v1','reemplazos y control laboral: fase posterior'],1110,410,275,165);
d+=box('e_task','TAREA DEL EVENTO',['PK tarea_id · FK evento_id','tipo: preparación/pedido/cambio','detalle · responsable · fecha límite','estado · evidencia / completada_en'],45,700,270,155);
d+=box('e_audit','AUDITORÍA DE CAMBIOS',['PK auditoría_id · usuario_id','entidad/registro/campo','valor anterior/nuevo · fecha','motivo · aprobador','cambios de precio, reserva, contrato'],390,700,280,170);
d+=box('e_refund','CANCELACIÓN / DEVOLUCIÓN',['PK cancelación_id · FK evento_id','solicitado_en · motivo/estado','monto aprobado · aprobado_por','FK pago_devolución nullable','condición ligada al contrato'],745,700,290,170);
d+=box('e_future','FASE POSTERIOR / CATÁLOGO',['INVENTARIO: artículo, variante/color, cantidad, movimiento/daño','GALERÍA: servicio/foto/orden/activo','Control de turnos, horas, pagos y adelantos','No mezclar con v1 hasta definir alcance'],1110,700,520,170,'shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=11;');
d+=rel('er1','e_event','e_contract','1 : N versiones');d+=rel('er2','e_event','e_pay','1 : N');d+=rel('er3','e_event','e_install','1 : N');d+=rel('er4','e_event','e_supplier_assign','1 : N');d+=rel('er5','e_supplier','e_supplier_assign','1 : N');d+=rel('er6','e_event','e_staff_assign','1 : N');d+=rel('er7','e_staff','e_staff_assign','1 : N');d+=rel('er8','e_event','e_task','1 : N');d+=rel('er9','e_event','e_audit','1 : N');d+=rel('er10','e_event','e_refund','0 : N');
d+=note('e_note','Decisión de diseño: no eliminar físicamente registros de negocio; conservar anulación y trazabilidad. U1 y el Doc. 3 asignan eliminación a ambos roles, así que la interfaz puede ofrecer eliminación lógica a ambos, registrando usuario y motivo. Política de retención por acordar.',45,940,790,85);
d+=note('e_note2','Datos personales confirmados: nombre, celular, dirección y DNI. Dirección permite identificar distrito de residencia/descuento; DNI sirve para corroborar datos. Aplicar acceso restringido y definir conservación/eliminación segura.',875,940,790,85);
save('Modelo_datos_Raices_03_Eventos_Pagos.drawio','Modelo de datos · eventos y pagos',d,1750,1050);

// View 4: post-v1 entities only, so the initial scope remains explicit.
let f='';
f+=box('f_title','MODELO LÓGICO · EXTENSIONES POSTERIORES',['Entidades previstas si se aprueba la ampliación del alcance'],390,25,950,55,'text;html=1;align=center;verticalAlign=middle;fontSize=19;fontStyle=1;fontColor=#2c5f4a;');
f+=box('i_item','ARTÍCULO DE INVENTARIO',['PK artículo_id · nombre/categoría','color · modelo · unidad · stock actual','mueble/mesa/silla/cristalería/tela/flor'],45,150,300,160);
f+=box('i_move','MOVIMIENTO DE INVENTARIO',['PK movimiento_id · FK artículo_id/evento_id','tipo: salida/devolución/daño/pérdida','cantidad · fecha · responsable','cargo/observación · estado'],430,150,300,165);
f+=box('g_service','SERVICIO / PAQUETE',['PK servicio_id · nombre/categoría','descripción · público/activo'],850,150,300,135);
f+=box('g_photo','FOTO DE GALERÍA',['PK foto_id · FK servicio_id','archivo/ruta · descripción · orden','autorización de uso · activo'],1230,150,300,150);
f+=box('s_person','PERSONAL',['PK personal_id · datos mínimos','contacto / función / activo'],45,470,300,130);
f+=box('s_shift','TURNO / REEMPLAZO',['PK turno_id · FK evento_id/personal_id','inicio/fin · función · horas','reemplaza_turno_id · estado'],430,470,300,160);
f+=box('s_settlement','PAGO DE PERSONAL',['PK liquidación_id · FK turno_id','importe/hora · total · adelantos','pagado_en · método · comprobante'],850,470,300,155);
f+=rel('fr1','i_item','i_move','1 : N');f+=rel('fr2','g_service','g_photo','1 : N');f+=rel('fr3','s_person','s_shift','1 : N');f+=rel('fr4','s_shift','s_settlement','1 : N');
f+=note('f_note','La gerente solicita horarios, turnos, reemplazos, horas, pagos y adelantos con prioridad alta; la priorización inicial de propietario/gerente dice que el control horario se posterga (el propietario también lo pospone). Este módulo queda diseñado como extensión hasta que ambos acuerden alcance y responsable.',45,760,790,110);
f+=note('f_note2','La galería de servicios fue solicitada como idea y el inventario como necesidad, pero ambos pueden quedar fuera de v1. Las entidades se muestran como diseño futuro; no asumir que ya son requisitos aprobados.',875,760,790,110);
save('Modelo_datos_Raices_04_Fase_Posterior.drawio','Modelo de datos · fase posterior',f,1600,920);
const readme=`DIAGRAMAS RAÍCES — BORRADOR V4, REGLAS V1 CONFIRMADAS

- Proceso_AS_IS_Raices.bpmn / Proceso_TO_BE_Raices.bpmn: BPMN 2.0 para importar y editar en Bizagi Modeler.
- Archivos .drawio: editar en diagrams.net.
- Modelo_de_datos_Raices.drawio: vista comercial/cotizaciones.
- Modelo_datos_Raices_02_Tarifas_Ambientes.drawio: tarifas, reglas, disponibilidad y ambientes.
- Modelo_datos_Raices_03_Eventos_Pagos.drawio: reservas, contratos, pagos, asignaciones y auditoría.
- Modelo_datos_Raices_04_Fase_Posterior.drawio: inventario, galería y extensión de control laboral.
- Casos_de_uso_Raices.drawio y Arquitectura_Raices.drawio: propuestas preliminares.

Fuentes: ZIP Instrumentos_Raices_llenos_v2, entrevistas U1/U2, documento de reglas, formularios y aclaraciones posteriores del investigador. En U1 varias respuestas remiten a lo que explicó U2; se conserva esa procedencia en vez de presentarlas como corroboraciones independientes.

REGLAS CONFIRMADAS PARA V1: una reserva por fecha; cotización vence a los 15 días y se libera la fecha si no se paga adelanto en ese plazo; adelanto permitido 10–30 %; paquetes básico, estándar y premium; el nuevo sistema reemplazará el generador HTML; se guardarán nombre, celular, DNI y dirección; turnos, reemplazos, horas y pagos del personal se posponen a versiones futuras. Ambos roles pueden eliminar según Documento 3; se propone baja lógica con auditoría.

Pendientes: quién puede autorizar una excepción fuera del adelanto 10–30 %; edades que separan tarifa de adulto/niño; importación de datos históricos de Drive/Excel al reemplazar el generador; reglas exactas de temporada/horario, calendario de cuotas y devolución; conservación y eliminación segura de DNI/dirección; definir en una futura versión si el salón dividido admitirá más de un evento por fecha.

Estos modelos son un diseño lógico preliminar basado en la información actualizada; no describen software implementado ni sustituyen la validación final de las reglas.
`;
fs.writeFileSync(path.join(out,'LEEME.txt'),readme,'utf8');
console.log('Generated expanded data model views and updated README.');
