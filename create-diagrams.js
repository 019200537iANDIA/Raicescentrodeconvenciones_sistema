const fs = require('fs');
const path = require('path');
const outDir = path.join(process.cwd(), 'Diagramas_Raices');
fs.mkdirSync(outDir, { recursive: true });
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

function makeBpmn(filename, name, laneName, nodes, flows, canvasW, canvasH) {
  const incoming = {}, outgoing = {};
  nodes.forEach(n => { incoming[n.id] = []; outgoing[n.id] = []; });
  flows.forEach(f => { outgoing[f.from].push(f.id); incoming[f.to].push(f.id); });
  const nodeXml = nodes.map(n => {
    const tag = n.type === 'task' ? (filename.includes('AS_IS') ? 'manualTask' : (n.id==='Task_Expire'||n.id==='Task_Followup' ? 'serviceTask' : 'userTask')) : n.type;
    const timer = n.timerDuration ? `<bpmn:timerEventDefinition><bpmn:timeDuration xsi:type="bpmn:tFormalExpression">${esc(n.timerDuration)}</bpmn:timeDuration></bpmn:timerEventDefinition>` : '';
    return `<bpmn:${tag} id="${n.id}" name="${esc(n.name)}">${incoming[n.id].map(x=>`<bpmn:incoming>${x}</bpmn:incoming>`).join('')}${outgoing[n.id].map(x=>`<bpmn:outgoing>${x}</bpmn:outgoing>`).join('')}${timer}</bpmn:${tag}>`;
  }).join('\n');
  const flowXml = flows.map(f => `<bpmn:sequenceFlow id="${f.id}" sourceRef="${f.from}" targetRef="${f.to}"${f.name?` name="${esc(f.name)}"`:''}/>`).join('\n');
  const refs = nodes.map(n => `<bpmn:flowNodeRef>${n.id}</bpmn:flowNodeRef>`).join('');
  const lane = `<bpmn:laneSet id="LaneSet_1"><bpmn:lane id="Lane_Internal" name="${esc(laneName)}">${refs}</bpmn:lane></bpmn:laneSet>`;
  const laneShape = `<bpmndi:BPMNShape id="Shape_Lane_Internal" bpmnElement="Lane_Internal" isHorizontal="true"><dc:Bounds x="20" y="20" width="${canvasW-40}" height="${canvasH-40}"/></bpmndi:BPMNShape>`;
  const shapeXml = nodes.map(n => `<bpmndi:BPMNShape id="Shape_${n.id}" bpmnElement="${n.id}"${n.type==='exclusiveGateway'?' isMarkerVisible="true"':''}><dc:Bounds x="${n.x}" y="${n.y}" width="${n.w}" height="${n.h}"/></bpmndi:BPMNShape>`).join('\n');
  const edgeXml = flows.map(f => {
    const a=nodes.find(n=>n.id===f.from), b=nodes.find(n=>n.id===f.to);
    const sx=a.x+a.w, sy=a.y+a.h/2, tx=b.x, ty=b.y+b.h/2;
    let points;
    if (Math.abs(sy-ty)<2) points=[[sx,sy],[tx,ty]];
    else { const mx=Math.max(sx+22,Math.min(tx-22,(sx+tx)/2)); points=[[sx,sy],[mx,sy],[mx,ty],[tx,ty]]; }
    return `<bpmndi:BPMNEdge id="Edge_${f.id}" bpmnElement="${f.id}">${points.map(p=>`<di:waypoint x="${p[0]}" y="${p[1]}"/>`).join('')}</bpmndi:BPMNEdge>`;
  }).join('\n');
  const xml=`<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="Definitions_Raices" targetNamespace="https://uandina.edu.pe/raices/bpmn">
 <bpmn:process id="Process_Raices" name="${esc(name)}" isExecutable="false">
  ${lane}
  ${nodeXml}
  ${flowXml}
 </bpmn:process>
 <bpmndi:BPMNDiagram id="Diagram_Raices"><bpmndi:BPMNPlane id="Plane_Raices" bpmnElement="Process_Raices">
  ${laneShape}${shapeXml}${edgeXml}
 </bpmndi:BPMNPlane></bpmndi:BPMNDiagram>
</bpmn:definitions>`;
  fs.writeFileSync(path.join(outDir,filename),xml,'utf8');
}

// BPMN 2.0 process map from the owner's and manager's interviews and observed workflow.
const asNodes=[
 {id:'Start_Consulta',type:'startEvent',name:'Llega una consulta',x:55,y:180,w:36,h:36},
 {id:'Task_Recibir',type:'task',name:'Recibir consulta y entender tipo de evento, fecha y asistentes',x:115,y:168,w:135,h:60},
 {id:'Task_Disponibilidad',type:'task',name:'Buscar fecha en calendarios físicos no sincronizados',x:275,y:168,w:145,h:60},
 {id:'Gateway_Fecha',type:'exclusiveGateway',name:'¿Fecha disponible?',x:445,y:173,w:50,h:50},
 {id:'Task_Alternativa',type:'task',name:'Informar o conversar otra fecha',x:525,y:55,w:140,h:60},
 {id:'End_NoFecha',type:'endEvent',name:'Oportunidad pendiente',x:700,y:67,w:36,h:36},
 {id:'Task_Precio',type:'task',name:'Consultar precios en Excel, mensajes o cuaderno',x:530,y:168,w:145,h:60},
 {id:'Task_Cotizar',type:'task',name:'Preparar cotización con generador HTML o formulario en papel',x:705,y:168,w:150,h:60},
 {id:'Task_Enviar',type:'task',name:'Guardar PDF en Drive y enviarlo al cliente',x:880,y:168,w:135,h:60},
 {id:'Gateway_Acepta',type:'exclusiveGateway',name:'¿Acepta/separa?',x:1045,y:173,w:50,h:50},
 {id:'Task_SinSeg',type:'task',name:'No existe seguimiento sistemático',x:1125,y:55,w:145,h:60},
 {id:'End_Perdida',type:'endEvent',name:'Oportunidad queda pendiente',x:1300,y:67,w:36,h:36},
 {id:'Task_Adelanto',type:'task',name:'Negociar y recibir adelanto',x:1120,y:168,w:135,h:60},
 {id:'Task_Contrato',type:'task',name:'Completar contrato en papel y anotar pago en cuaderno',x:1280,y:168,w:150,h:60},
 {id:'Task_Bloquear',type:'task',name:'Marcar reserva en calendario físico',x:1455,y:168,w:140,h:60},
 {id:'Task_Coord',type:'task',name:'Coordinar detalles con cliente, proveedores y personal por llamadas/reuniones',x:1620,y:168,w:160,h:60},
 {id:'End_Evento',type:'endEvent',name:'Evento atendido',x:1805,y:180,w:36,h:36}
];
const asFlows=[
 {id:'F01',from:'Start_Consulta',to:'Task_Recibir'},{id:'F02',from:'Task_Recibir',to:'Task_Disponibilidad'},{id:'F03',from:'Task_Disponibilidad',to:'Gateway_Fecha'},
 {id:'F04',from:'Gateway_Fecha',to:'Task_Alternativa',name:'No'},{id:'F05',from:'Task_Alternativa',to:'End_NoFecha'},
 {id:'F06',from:'Gateway_Fecha',to:'Task_Precio',name:'Sí'},{id:'F07',from:'Task_Precio',to:'Task_Cotizar'},{id:'F08',from:'Task_Cotizar',to:'Task_Enviar'},{id:'F09',from:'Task_Enviar',to:'Gateway_Acepta'},
 {id:'F10',from:'Gateway_Acepta',to:'Task_SinSeg',name:'No / no responde'},{id:'F11',from:'Task_SinSeg',to:'End_Perdida'},
 {id:'F12',from:'Gateway_Acepta',to:'Task_Adelanto',name:'Sí'},{id:'F13',from:'Task_Adelanto',to:'Task_Contrato'},{id:'F14',from:'Task_Contrato',to:'Task_Bloquear'},{id:'F15',from:'Task_Bloquear',to:'Task_Coord'},{id:'F16',from:'Task_Coord',to:'End_Evento'}
];
makeBpmn('Proceso_AS_IS_Raices.bpmn','Proceso actual de consulta, cotización y reserva','Gerencia / propietario — herramientas actuales',asNodes,asFlows,1885,300);

const toNodes=[
 {id:'Start_Consulta',type:'startEvent',name:'Llega una consulta',x:45,y:180,w:36,h:36},
 {id:'Task_Lead',type:'task',name:'Registrar/actualizar cliente, canal y oportunidad',x:100,y:168,w:140,h:60},
 {id:'Task_Cal',type:'task',name:'Consultar disponibilidad; una reserva por fecha en v1',x:260,y:168,w:130,h:60},
 {id:'Gateway_Fecha',type:'exclusiveGateway',name:'¿Fecha disponible?',x:410,y:173,w:50,h:50},
 {id:'Task_Alternativa',type:'task',name:'Proponer fecha alternativa y volver a consultar',x:490,y:55,w:145,h:60},
 {id:'End_NoFecha',type:'endEvent',name:'Sin fecha acordada',x:670,y:67,w:36,h:36},
 {id:'Task_ConfigQuote',type:'task',name:'Configurar evento, asistentes, paquete y servicios',x:485,y:168,w:145,h:60},
 {id:'Gateway_DirectStart',type:'exclusiveGateway',name:'¿Reserva directa?',x:480,y:265,w:50,h:50},
 {id:'Task_Price',type:'task',name:'Calcular precio editable y generar cotización PDF',x:655,y:168,w:145,h:60},
 {id:'Task_Send',type:'task',name:'Enviar cotización; mostrar cantidad y total/persona',x:825,y:168,w:140,h:60},
 {id:'Task_Followup',type:'task',name:'Programar contacto a 2–3 días y segundo contacto si hace falta',x:990,y:168,w:160,h:60},
 {id:'Task_Contact',type:'task',name:'Contactar al cliente y registrar respuesta',x:1170,y:168,w:155,h:60},
 {id:'Gateway_Race',type:'eventBasedGateway',name:'Esperar respuesta o vencimiento configurado',x:1360,y:173,w:50,h:50},
 {id:'Catch_Response',type:'intermediateCatchEvent',name:'Cliente responde',x:1450,y:95,w:38,h:38},
 {id:'Catch_Expiry',type:'intermediateCatchEvent',name:'Vence cotización a los 15 días',x:1450,y:240,w:38,h:38,timerDuration:'P15D'},
 {id:'Gateway_Accept',type:'exclusiveGateway',name:'¿Acepta la propuesta?',x:1530,y:92,w:50,h:50},
 {id:'Task_Expire',type:'task',name:'Cerrar oportunidad y liberar fecha si no pagó adelanto',x:1540,y:235,w:175,h:60},
 {id:'End_Expire',type:'endEvent',name:'Oportunidad cerrada',x:1720,y:247,w:36,h:36},
 {id:'Task_Deposit',type:'task',name:'Registrar adelanto acordado entre 10 % y 30 %',x:1630,y:92,w:170,h:60},
 {id:'Task_Reserve',type:'task',name:'Registrar reserva y bloquear fecha (máximo 1 por fecha)',x:1900,y:92,w:175,h:60},
 {id:'Task_Contract',type:'task',name:'Generar contrato y registrar pagos',x:2090,y:92,w:145,h:60},
 {id:'Gateway_Cancel',type:'exclusiveGateway',name:'¿Solicita cancelar reserva?',x:2180,y:300,w:50,h:50},
 {id:'Task_Coord',type:'task',name:'Coordinar proveedor, personal, especificaciones y cambios',x:2260,y:92,w:155,h:60},
 {id:'Gateway_Change',type:'exclusiveGateway',name:'¿Solicita cambios?',x:2430,y:97,w:50,h:50},
 {id:'Task_Change',type:'task',name:'Registrar cambio, nueva versión y aprobación; actualizar contrato',x:2510,y:195,w:175,h:60},
 {id:'End_Evento',type:'endEvent',name:'Evento ejecutado',x:2720,y:104,w:36,h:36},
 {id:'Task_Cancel',type:'task',name:'Registrar cancelación, revisar contrato y devolución autorizada',x:1800,y:300,w:185,h:60},
 {id:'End_Cancel',type:'endEvent',name:'Reserva cancelada',x:1840,y:312,w:36,h:36}
];
const toFlows=[
 {id:'T01',from:'Start_Consulta',to:'Task_Lead'},{id:'T02',from:'Task_Lead',to:'Task_Cal'},{id:'T03',from:'Task_Cal',to:'Gateway_Fecha'},
 {id:'T04',from:'Gateway_Fecha',to:'Task_Alternativa',name:'No'},{id:'T05',from:'Task_Alternativa',to:'Task_Cal'},
 {id:'T06',from:'Gateway_Fecha',to:'Gateway_DirectStart',name:'Sí'},{id:'T06a',from:'Gateway_DirectStart',to:'Task_ConfigQuote',name:'Cotización'},{id:'T06b',from:'Gateway_DirectStart',to:'Task_Deposit',name:'Reserva directa (validar reglas)'},{id:'T07',from:'Task_ConfigQuote',to:'Task_Price'},{id:'T08',from:'Task_Price',to:'Task_Send'},{id:'T09',from:'Task_Send',to:'Task_Followup'},{id:'T10',from:'Task_Followup',to:'Task_Contact'},{id:'T10a',from:'Task_Contact',to:'Gateway_Race'},
 {id:'T11',from:'Gateway_Race',to:'Catch_Response'},{id:'T12',from:'Gateway_Race',to:'Catch_Expiry'},{id:'T13',from:'Catch_Expiry',to:'Task_Expire'},{id:'T14',from:'Task_Expire',to:'End_Expire'},
 {id:'T15',from:'Catch_Response',to:'Gateway_Accept'},{id:'T16',from:'Gateway_Accept',to:'Task_Deposit',name:'Sí / negociación confirmada'},{id:'T17',from:'Gateway_Accept',to:'End_Expire',name:'No / oportunidad cerrada'},
 {id:'T18',from:'Task_Deposit',to:'Task_Reserve'},{id:'T20',from:'Task_Reserve',to:'Task_Contract'},{id:'T21',from:'Task_Contract',to:'Gateway_Cancel'},{id:'T22',from:'Gateway_Cancel',to:'Task_Cancel',name:'Sí'},{id:'T23',from:'Gateway_Cancel',to:'Task_Coord',name:'No'},{id:'T24',from:'Task_Coord',to:'Gateway_Change'},{id:'T25',from:'Gateway_Change',to:'Task_Change',name:'Sí'},{id:'T26',from:'Task_Change',to:'Task_Contract',name:'Reemitir contrato'},{id:'T27',from:'Gateway_Change',to:'End_Evento',name:'No'},
 {id:'T28',from:'Task_Cancel',to:'End_Cancel'}
];
makeBpmn('Proceso_TO_BE_Raices.bpmn','Proceso propuesto integrado de consulta, cotización y reserva','Sistema integrado — usuario interno',toNodes,toFlows,2850,380);

function cell(id,value,style,x,y,w,h,parent='1',extra='') { return `<mxCell id="${id}" value="${esc(value)}" style="${style}" vertex="1" parent="${parent}"><mxGeometry x="${x}" y="${y}" width="${w}" height="${h}" as="geometry"/>${extra}</mxCell>`; }
function edge(id,source,target,value='') { return `<mxCell id="${id}" value="${esc(value)}" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;endArrow=block;endFill=1;strokeColor=#526b60;fontSize=11;" edge="1" parent="1" source="${source}" target="${target}"><mxGeometry relative="1" as="geometry"/></mxCell>`; }
function drawio(filename,title,cells,width=1800,height=1200){
 const xml=`<mxfile host="app.diagrams.net" modified="2026-11-15T00:00:00.000Z" agent="Codex" version="24.7.17"><diagram id="${filename}" name="${esc(title)}"><mxGraphModel dx="${width}" dy="${height}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="${width}" pageHeight="${height}" math="0" shadow="0"><root><mxCell id="0"/><mxCell id="1" parent="0"/>${cells}</root></mxGraphModel></diagram></mxfile>`;
 fs.writeFileSync(path.join(outDir,filename),xml,'utf8');
}
const green='fillColor=#e4ece7;strokeColor=#2c5f4a;fontColor=#18241f;';

// Diagrams.net BPMN-readable process sketches for quick preview/editing; canonical process files are the BPMN 2.0 files above.
function flowSketch(filename,titleText,steps,notes){
 let c=cell('head',titleText,'text;html=1;align=center;verticalAlign=middle;fontSize=20;fontStyle=1;fontColor=#2c5f4a;',40,25,1600,50);
 const ids=[];steps.forEach((s,i)=>{const id='n'+i;ids.push(id);let st=s.type==='event'?'ellipse;whiteSpace=wrap;html=1;aspect=fixed;fillColor=#d5eee1;strokeColor=#2c7a4b;':(s.type==='gateway'||s.type==='eventGateway')?'rhombus;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;':'rounded=1;whiteSpace=wrap;html=1;fillColor=#e4ece7;strokeColor=#2c5f4a;';const col=i%5,row=Math.floor(i/5);c+=cell(id,s.name,st,50+col*315,115+row*175,240,(s.type==='gateway'||s.type==='eventGateway')?75:85);});
 let ei=0;steps.forEach((s,i)=>{const es=s.edges||(s.next!==undefined?[{to:s.next,label:s.label||''}]:[]);es.forEach(a=>c+=edge('e'+(ei++),ids[i],ids[a.to],a.label||''));});
 const rows=Math.ceil(steps.length/5),noteY=130+rows*175;
 notes.forEach((n,i)=>{c+=cell('note'+i,n,'shape=note;whiteSpace=wrap;html=1;backgroundOutline=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=12;',55+i*510,noteY,470,125);});
 drawio(filename,titleText,c,1700,noteY+170);
}
flowSketch('Proceso_AS_IS_Raices.drawio','AS-IS · Proceso actual',[
 {name:'Inicio: recibir consulta por WhatsApp, redes, llamada o presencial',type:'event',next:1},
 {name:'Buscar fecha en calendarios físicos no sincronizados',next:2},
 {name:'¿Fecha disponible?',type:'gateway',edges:[{to:3,label:'No'},{to:4,label:'Sí'}]},
 {name:'Conversar fecha alternativa',next:1},
 {name:'Consultar precios en Excel, mensajes o cuaderno',next:5},
 {name:'Preparar cotización con generador HTML o papel',next:6},
 {name:'Guardar PDF en Drive y enviarlo al cliente',next:7},
 {name:'¿Cliente acepta/separa?',type:'gateway',edges:[{to:8,label:'Sí'},{to:9,label:'No / no responde'}]},
 {name:'Negociar adelanto; completar contrato en papel',next:10},
 {name:'No existe registro sistemático de seguimiento',next:11},
 {name:'Anotar pago y reserva en cuaderno/calendario físico',next:12},
 {name:'Coordinar con cliente, proveedores y personal por llamadas/reuniones',next:13},
 {name:'Fin: evento atendido',type:'event'},
 {name:'Oportunidad pendiente',type:'event'}
],[
 'Puntos de dolor medidos: 19 min para buscar en cuaderno; 11 min para cotizar (una observación); 25 min para reserva (dato reportado); 70 min de coordinación (dato reportado). No son promedios comparables.',
 'No hay seguimiento sistemático cuando el cliente no acepta o no responde. Reserva directa y fecha alternativa quedan como variantes por validar con los responsables.',
 'Fuentes dispersas: generador HTML, Drive, Excel, cuaderno, WhatsApp y calendarios. Este flujo es el canónico preliminar; confirmar con la gerente.'
]);
flowSketch('Proceso_TO_BE_Raices.drawio','TO-BE · Proceso propuesto',[
 {name:'Inicio: consulta',type:'event',next:1},
 {name:'Registrar/actualizar cliente, canal y oportunidad',next:2},
 {name:'Consultar disponibilidad; v1 permite una sola reserva por fecha',next:3},
 {name:'¿Fecha disponible?',type:'gateway',edges:[{to:4,label:'No'},{to:22,label:'Sí'}]},
 {name:'Proponer fecha alternativa y volver a consultar',next:2},
 {name:'Configurar tipo de evento, asistentes, paquete y servicios',next:6},
 {name:'Calcular precio según reglas configurables y emitir cotización',next:7},
 {name:'Enviar cotización; contactar a los 2–3 días; registrar respuesta y segundo contacto si hace falta',next:8},
 {name:'Esperar respuesta del cliente o vencimiento a los 15 días',type:'eventGateway',edges:[{to:9,label:'Respuesta'},{to:10,label:'Vence 15 días'}]},
 {name:'Registrar respuesta; ¿acepta?',type:'gateway',edges:[{to:11,label:'Sí'},{to:20,label:'No'}]},
 {name:'Cerrar oportunidad y liberar fecha si no pagó adelanto',next:20},
 {name:'Registrar adelanto acordado entre 10 % y 30 %',next:12},
 {name:'Registrar reserva y bloquear fecha; máximo una por fecha en v1',next:13},
 {name:'Generar contrato y registrar pagos/cuotas',edges:[{to:14,label:'Continúa'},{to:16,label:'Solicita cancelar'}]},
 {name:'Coordinar asignaciones separadas de proveedor y personal',next:17},
 {name:'¿Hay cambio solicitado?',type:'gateway',edges:[{to:18,label:'Sí'},{to:19,label:'No'}]},
 {name:'Registrar cancelación y evaluar devolución según contrato',next:21},
 {name:'Registrar especificaciones y fechas límite de pedidos',next:15},
 {name:'Registrar nueva versión, aprobación y actualizar contrato',next:13},
 {name:'Fin: evento atendido',type:'event'},
 {name:'Fin: oportunidad vencida/cerrada',type:'event'},
 {name:'Fin: reserva cancelada',type:'event'},
 {name:'¿Reserva directa?',type:'gateway',edges:[{to:11,label:'Sí: directa'},{to:5,label:'No: cotizar'}]}
],[
 'Cotización: 15 días. Sin adelanto al vencimiento, se cierra la oportunidad y se libera la fecha. Adelanto: 10–30 %.',
 'V1 reemplaza el generador HTML. Reportes son transversales; turnos, reemplazos, horas y pagos del personal pasan a una versión futura.',
 'V1: una reserva por fecha. La división del salón no permite todavía dos eventos simultáneos; queda como ampliación futura.'
]);

// UML Use Case diagram.
let uc='';
uc+=cell('sys','Sistema de información · Raíces','swimlane;html=1;startSize=32;rounded=0;fillColor=#f8fbf8;strokeColor=#2c5f4a;fontStyle=1;fontSize=16;',260,65,1050,710);
uc+=cell('actor1','Propietario','shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#d5eee1;strokeColor=#2c5f4a;',45,190,80,120);
uc+=cell('actor2','Gerente','shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#d5eee1;strokeColor=#2c5f4a;',45,470,80,120);
const uses=[['uc1','Administrar clientes y consultas',430,125],['uc2','Crear / editar cotización y calcular precio',700,125],['uc3','Generar PDF de cotización',970,125],['uc4','Consultar calendario / disponibilidad',430,245],['uc5','Registrar reserva directa / adelanto',700,245],['uc6','Modificar evento / contrato',970,245],['uc7','Dar seguimiento a cotización',430,365],['uc8','Coordinar proveedores y personal',700,365],['uc9','Registrar pagos / saldos / devolución',970,365],['uc10','Consultar reportes e historial del cliente',565,505],['uc11','Configurar servicios / descuentos',835,505],['uc12','Administrar usuarios y respaldos',700,635],['uc13','Iniciar sesión',430,635],['uc14','Eliminar/anular registros*',970,635],['uc15','Liberar fechas y enviar recordatorios',1120,505]];
uses.forEach(u=>uc+=cell(u[0],u[1],'ellipse;whiteSpace=wrap;html=1;fillColor=#e4ece7;strokeColor=#2c5f4a;fontSize=12;',u[2],u[3],220,62,'1'));
uses.forEach(u=>{uc+=edge('own_'+u[0],'actor1',u[0]);uc+=edge('mgr_'+u[0],'actor2',u[0]);});
uc+=cell('actorSystem','Sistema','shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fillColor=#dce6f1;strokeColor=#557299;',1340,570,80,120);
uc+=edge('sys_uc15','actorSystem','uc15');
uc+=`<mxCell id="include_pdf" value="&lt;&lt;include&gt;&gt;" style="edgeStyle=orthogonalEdgeStyle;dashed=1;endArrow=open;html=1;strokeColor=#557299;fontSize=10;" edge="1" parent="1" source="uc2" target="uc3"><mxGeometry relative="1" as="geometry"/></mxCell>`;
uc+=cell('ucnote','Doc. 3 asigna eliminar registros a ambos roles. Implementar preferentemente baja lógica con motivo y auditoría para poder rastrear quién anuló el registro. Los demás permisos se muestran comunes según la matriz del Documento 3.','shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=11;',320,780,1000,70);
drawio('Casos_de_uso_Raices.drawio','Casos de uso UML',uc,1400,900);

// Entity relationship model with version-one scope separated from deferred functions.
let er='';
const entities=[
 ['eUser','USUARIO','PK id\nNombre\nRol: propietario / gerente\nActivo',50,70,220,125],
 ['eCustomer','CLIENTE','PK id\nNombre\nCelular\nResidencia/distrito (mínimo necesario)\nTipo: nuevo / recurrente',360,70,240,155],
 ['eLead','CONSULTA','PK id\nFK cliente_id\nCanal / fecha\nEstado / nota',700,70,230,140],
 ['eFollow','SEGUIMIENTO','PK id\nFK consulta_id / cotización_id\nFecha próxima acción\nEstado / usuario_id',1030,70,245,155],
 ['eQuote','COTIZACIÓN','PK id\nFK cliente_id / usuario_id\nFK tipo_evento / ambiente\nFecha / adultos / niños\nVigencia y adelanto configurables\nEstado / total / cotización_padre_id',360,330,260,185],
 ['eItem','DETALLE_COTIZACIÓN','PK id\nFK cotización_id / servicio_id\nDescripción / cantidad\nPrecio interno / subtotal',700,330,250,155],
 ['eService','SERVICIO / PAQUETE','PK id\nNombre / categoría\nPrecio base configurable\nOpciones / activo',1030,330,245,155],
 ['eEvent','EVENTO / RESERVA','PK id\nFK cliente_id / cotización_id (nullable)\nFK tipo_evento / ambiente\nFecha / adultos / niños\nEstado / contrato versionado',360,635,260,175],
 ['ePayment','PAGO','PK id / FK evento_id / usuario_id\nFecha / monto / método\nComprobante / tipo / estado\nAdelanto, cuota, saldo o devolución',700,635,250,155],
 ['eVendor','PROVEEDOR','PK id\nNombre / servicio\nContacto / notas',1030,635,245,125],
 ['eCoord','ASIG_PROVEEDOR','PK id / FK evento_id / proveedor_id\nEspecificación / fecha límite\nEntrega / cambios / estado',1350,330,260,165],
 ['eStaff','PERSONAL','PK id\nNombre / función\nContacto / activo\nAlcance v1 por confirmar',1350,635,260,125],
 ['eType','TIPO_EVENTO','PK id / nombre\nMatrimonio, 15 años, graduación, corporativo',50,330,240,130],
 ['eVenue','AMBIENTE','PK id / nombre\nÁrea verde, salón completo, medio salón, mezanine\nCombinaciones por validar',50,635,240,145],
 ['eVersion','VERSION_COTIZACION','PK id / FK cotización_id\nNúmero / fecha / autor\nTotal / motivo / aprobación',700,70,250,155],
 ['eRule','REGLA_TARIFA','PK id / criterios configurables\nTemporada / día / horario\nRango de personas / promoción L–J\nResidencia y % / autorizador\nVigencia / aprobación',1030,70,255,175],
 ['eInstall','CUOTA','PK id / FK evento_id\nVencimiento / importe / estado\nFecha pagada / comprobante',700,850,250,135],
 ['eAudit','AUDITORIA_CAMBIO','PK id / entidad / registro_id\nCampo / valor anterior-nuevo\nFecha / usuario / motivo / aprobador',1030,850,255,155],
 ['eAssignStaff','ASIG_PERSONAL','PK id / FK evento_id / personal_id\nFunción / turno / horario\nReemplazo / pago / adelanto\nAlcance v1 por confirmar',1350,850,260,165]
];
entities.forEach(e=>er+=cell(e[0],`<b>${e[1]}</b><br>${e[2].replace(/\n/g,'<br>')}`,'rounded=0;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacing=7;fillColor=#f8fbf8;strokeColor=#2c5f4a;fontSize=11;',e[3],e[4],e[5],e[6]));
const rels=[['r1','eCustomer','eLead','1 : N'],['r2','eCustomer','eQuote','1 : N'],['r3','eLead','eFollow','1 : N'],['r4','eQuote','eFollow','1 : N'],['r5','eQuote','eItem','1 : N'],['r6','eService','eItem','1 : N'],['r7','eCustomer','eEvent','1 : N'],['r8','eQuote','eEvent','0..1 : 0..1'],['r9','eEvent','ePayment','1 : N'],['r10','eEvent','eCoord','1 : N'],['r11','eVendor','eCoord','1 : N'],['r12','eStaff','eAssignStaff','1 : N'],['r13','eUser','eQuote','1 : N'],['r14','eUser','ePayment','1 : N'],['r15','eQuote','eVersion','1 : N'],['r16','eType','eQuote','1 : N'],['r17','eType','eEvent','1 : N'],['r18','eVenue','eQuote','1 : N'],['r19','eVenue','eEvent','1 : N'],['r20','eRule','eQuote','aplica'],['r21','eEvent','eInstall','1 : N'],['r22','eEvent','eAudit','1 : N'],['r23','eEvent','eAssignStaff','1 : N']];
rels.forEach(r=>er+=edge(r[0],r[1],r[2],r[3]));
er+=cell('defer','Por confirmar: dos eventos en un día al dividir ambientes; nombres de paquetes (intermedio/estándar); si turnos, reemplazos, horas, pagos y adelantos de personal entran en v1. Galería de servicios/fotos como posible fase posterior.','shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=12;',50,1050,700,115);
er+=cell('designnote','Pendientes: vigencia de cotización; adelanto y bloqueo; descuentos y autorizador; cuotas/devoluciones. Deben ser configurables y auditables. Preferir residencia/distrito mínimo; no exigir DNI/dirección completa sin necesidad aprobada. Buscar cliente repetido por celular e historial. Reserva directa permitida con cotización opcional, a validar.','shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=12;',800,1050,800,115);
drawio('Modelo_de_datos_Raices.drawio','Modelo de datos preliminar',er,1700,1250);

// 3-tier architecture aligned to the proposal; integrations remain manual unless separately approved.
let ar='';
ar+=cell('title','Arquitectura preliminar de tres capas','text;html=1;align=center;verticalAlign=middle;fontSize=20;fontStyle=1;fontColor=#2c5f4a;',350,25,850,45);
ar+=cell('users','Usuarios internos<br>Propietario · Gerente','rounded=1;whiteSpace=wrap;html=1;fillColor=#d5eee1;strokeColor=#2c7a4b;fontSize=14;',540,100,430,70);
ar+=cell('ui','PRESENTACIÓN · Filament<br>Interfaz web adaptable<br>Laravel / PHP · navegador','rounded=1;whiteSpace=wrap;html=1;fillColor=#e4ece7;strokeColor=#2c5f4a;fontSize=14;',500,230,510,105);
ar+=cell('app','APLICACIÓN · Laravel / PHP<br>Clientes y consultas · cotizaciones y precios<br>Disponibilidad / reservas · seguimiento<br>Proveedores y personal · pagos / reportes<br>Autenticación · permisos · generación PDF','rounded=1;whiteSpace=wrap;html=1;fillColor=#e4ece7;strokeColor=#2c5f4a;fontSize=13;',400,410,710,160);
ar+=cell('db','DATOS · MySQL<br>Clientes · consultas · cotizaciones<br>eventos/reservas · pagos · coordinación<br>catálogo · usuarios · historial','shape=cylinder3;whiteSpace=wrap;html=1;boundedLbl=1;backgroundOutline=1;size=15;fillColor=#dce6f1;strokeColor=#557299;fontSize=13;',510,650,490,145);
ar+=edge('a1','users','ui','HTTPS / navegador');ar+=edge('a2','ui','app','solicitudes web');ar+=edge('a3','app','db','consultas / transacciones');
ar+=cell('external','Canales externos actuales<br>WhatsApp · Facebook · Instagram · TikTok · llamadas<br>Contacto manual; no hay API aprobada.','shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=12;',35,240,330,110);
ar+=cell('legacy','Reemplazar generador HTML por sistema integrado<br>Revisar migración histórica desde Drive/Excel<br>Cuadernos · calendarios físicos','shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=12;',1150,240,370,130);
ar+=cell('infra','Infraestructura pendiente de decidir<br>Hosting / dominio / copias de seguridad<br>Conectividad móvil actual; Wi-Fi planificado<br>Sin modo sin conexión confirmado.','shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=12;',1150,475,370,130);
ar+=edge('a4','external','ui','interacción fuera del sistema');ar+=edge('a5','legacy','app','migración histórica por definir');ar+=edge('a6','app','infra','despliegue por decidir');
ar+=cell('scheduler','TAREAS PROGRAMADAS<br>Laravel Scheduler / cola<br>Liberar fechas por regla confirmada<br>Enviar recordatorios; vigencia configurable','rounded=1;whiteSpace=wrap;html=1;fillColor=#e4ece7;strokeColor=#2c5f4a;fontSize=12;',1150,665,370,115);
ar+=cell('backupaudit','RESPALDO Y AUDITORÍA<br>Copias de seguridad (RNF03)<br>Registro de cambios y autorizaciones<br>Retención y acceso por definir','shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6a800;fontSize=12;',35,470,330,120);
ar+=edge('a7','scheduler','app','recordatorios / vencimientos');ar+=edge('a8','backupaudit','db','respaldo y trazabilidad');
ar+=cell('note','Tecnologías propuestas por el Word: PHP/Laravel, Filament y MySQL. Esta arquitectura es un diseño preliminar, no una implementación verificada. El diseño final debe respetar la conectividad disponible y la política de datos acordada.','shape=note;whiteSpace=wrap;html=1;fillColor=#fce4d6;strokeColor=#b4532a;fontSize=12;',350,850,850,100);
drawio('Arquitectura_Raices.drawio','Arquitectura tres capas',ar,1600,1050);

const readme=`DIAGRAMAS RAÍCES — BORRADOR V2 REVISADO\n\n- Proceso_AS_IS_Raices.bpmn y Proceso_TO_BE_Raices.bpmn: BPMN 2.0 editables/importables en Bizagi Modeler.\n- Archivos .drawio: editables en diagrams.net. Los BPMN y sus vistas Draw.io deben tratarse como representaciones del mismo flujo lógico, todavía sujetos a validación de los responsables.\n- Casos de uso UML, modelo de datos y arquitectura son propuestas preliminares.\n\nBase documental: principalmente el levantamiento de la gerente (U2) y reglas compartidas; el propietario (U1) remitió varios puntos a lo ya explicado a la gerente. Su validación específica de los detalles sigue pendiente. No se consideran dos entrevistas independientes plenamente corroboradas.\n\nPENDIENTES ANTES DE CONGELAR EL DISEÑO: vigencia de cotización y duración de separación (una semana/15 días); porcentaje/rango y condiciones del adelanto; momento de bloqueo de fecha y liberación; dos eventos por día al dividir ambientes; paquetes intermedio/estándar; volumen mensual (2–3 / hasta 13); turnos/pagos de personal en v1; reemplazar o integrar generador actual; permisos para eliminar; calendario de cuotas, cancelación y devolución; definir reglas de tarifa/descuentos y su autorización; confirmar reserva directa y variantes de flujo.\n\nLos porcentajes, vigencias y reglas disputadas se modelan como configuración pendiente y no como políticas aprobadas. El modelo minimiza datos personales; validar residencia/distrito y necesidad de cualquier dato adicional. No incluye datos reales de clientes.\nEstos son borradores de diseño, no evidencia de que el sistema esté implementado ni importación validada en Bizagi.\n`;
fs.writeFileSync(path.join(outDir,'LEEME.txt'),readme,'utf8');
console.log('Created diagram drafts in '+outDir);
require('./generate-model-v2.js');
