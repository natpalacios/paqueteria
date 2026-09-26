

const MOCK_DATA = {
  'resumen.php': {
    envios_totales: 6,
    en_transito: 2,
    entregados: 2,
    pendientes: 2,
    viajes_activos: 3,
    viajes_total: 4,
    zonas_activas: 4,
    fecha_operacion: new Date().toISOString().slice(0, 10),
  },
  'monitoreo.php': [
    { id: 1, vehiculo: 'M-4821-N', conductor: 'Ramón Alberto Gutiérrez', estado: 'en_ruta',      lat: 12.1364, lng: -86.2514, ultimo_reporte: new Date(Date.now() - 1 * 60000).toISOString(),  zona: 'Managua Centro' },
    { id: 2, vehiculo: 'M-7710-N', conductor: 'Karla Vanessa Mendoza',   estado: 'detenido',     lat: 11.8497, lng: -86.1994, ultimo_reporte: new Date(Date.now() - 6 * 60000).toISOString(),  zona: 'Jinotepe' },
    { id: 3, vehiculo: 'CD-1129',  conductor: 'Ervin José Tórrez',       estado: 'en_ruta',      lat: 11.9720, lng: -86.0963, ultimo_reporte: new Date(Date.now() - 2 * 60000).toISOString(),  zona: 'Masaya' },
    { id: 4, vehiculo: 'M-9902-N', conductor: 'Xiomara Isabel Baca',     estado: 'sin_conexion', lat: 11.9280, lng: -86.2870, ultimo_reporte: new Date(Date.now() - 47 * 60000).toISOString(), zona: 'Carretera Sur' },
  ],
  'zonas.php': [
    { nombre: 'Managua Centro', cumplimiento_pct: 92.5 },
    { nombre: 'Masaya',         cumplimiento_pct: 88.4 },
    { nombre: 'Jinotepe',       cumplimiento_pct: 81.0 },
    { nombre: 'Carretera Sur',  cumplimiento_pct: 76.2 },
  ],
  'alertas.php': [
    { id: 1, tipo: 'retraso',        descripcion: 'Viaje con retraso de 25 min sobre lo previsto',              relacionado_con: 'Viaje #2 · M-7710-N', zona: 'Jinotepe',      hora: new Date(Date.now() - 20 * 60000).toISOString() },
    { id: 2, tipo: 'incidencia',     descripcion: 'Vehículo sin reportar posición hace más de 45 min',          relacionado_con: 'Viaje #4 · M-9902-N', zona: 'Carretera Sur', hora: new Date(Date.now() - 47 * 60000).toISOString() },
    { id: 3, tipo: 'paquete_danado', descripcion: 'Paquete GSL-10236 reportado con daño en empaque',            relacionado_con: 'Envío GSL-10236',     zona: 'Masaya',        hora: new Date(Date.now() - 90 * 60000).toISOString() },
  ],
  'actividades.php': [
    { descripcion: 'Envío GSL-10239 entregado en Masaya',                          tipo: 'completado', creado_en: new Date(Date.now() - 15 * 60000).toISOString() },
    { descripcion: 'Viaje #4 asignado a Xiomara Baca (M-9902-N)',                  tipo: 'asignado',   creado_en: new Date(Date.now() - 40 * 60000).toISOString() },
    { descripcion: 'Alerta registrada por retraso en Ruta Jinotepe-Diriamba',      tipo: 'incidencia', creado_en: new Date(Date.now() - 20 * 60000).toISOString() },
    { descripcion: 'Envío GSL-10237 entregado en Masaya',                         tipo: 'completado', creado_en: new Date(Date.now() - 3 * 60 * 60000).toISOString() },
  ],
};

const API = 'api';

const ESTADO_LABEL = {
  en_ruta: 'En ruta',
  detenido: 'Detenido',
  sin_conexion: 'Sin conexión',
};

const TIPO_ICONO = {
  retraso: '⏱️',
  incidencia: '⚠️',
  reasignacion: '🔁',
  paquete_danado: '📦',
  cliente_ausente: '🙍',
};

const TIPO_LABEL = {
  retraso: 'Retraso',
  incidencia: 'Incidencia',
  reasignacion: 'Reasignación',
  paquete_danado: 'Paquete dañado',
  cliente_ausente: 'Cliente ausente',
};
async function getJSON(endpoint) {
  if (!(endpoint in MOCK_DATA)) throw new Error(`Sin datos de ejemplo para ${endpoint}`);
  return JSON.parse(JSON.stringify(MOCK_DATA[endpoint]));
}

function timeAgo(dateStr) {
  const diffMs = Date.now() - new Date(dateStr).getTime();
  const mins = Math.round(diffMs / 60000);
  if (mins < 1) return 'Hace un momento';
  if (mins < 60) return `Hace ${mins} min`;
  const hours = Math.round(mins / 60);
  return `Hace ${hours} h`;
}

function pintarFecha(fechaISO) {
  const fecha = fechaISO ? new Date(fechaISO + 'T00:00:00') : new Date();
  document.getElementById('today').textContent = fecha.toLocaleDateString('es-NI', {
    day: 'numeric', month: 'long', year: 'numeric',
  });
}

function iconSVG(name) {
  const icons = {
    cube: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 7.2v9.6L12 21l7.5-4.2V7.2L12 3Z"/><path d="m4.8 7.4 7.2 4 7.2-4M12 11.4V21"/></svg>',
    truck: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v10H3zM14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
    check: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="m8.5 12 2.3 2.3 4.8-5"/></svg>',
    clock: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5l3 1.8"/></svg>',
    users: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.8 18.5c.7-3 2.3-4.5 5.2-4.5s4.5 1.5 5.2 4.5M16 8.5a2.7 2.7 0 1 1 0 5.4M16.5 14.5c2.2.2 3.4 1.5 3.9 3.8"/></svg>',
    flag: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4"/><path d="M5 5c4-3 7 3 14 0v9c-7 3-10-3-14 0"/></svg>',
    bike: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="17" r="3.2"/><circle cx="18" cy="17" r="3.2"/><path d="m6 17 4-7h3l5 7M9 10h-2M13 10l-2 7h7M12 7h2"/></svg>',
    alert: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 8 15H4L12 4Z"/><path d="M12 9v4M12 16h.01"/></svg>'
  };
  return icons[name] || icons.cube;
}

async function renderResumen() {
  const r = await getJSON('resumen.php');
  pintarFecha(r.fecha_operacion);
  const cards = [
    { label: 'Envíos totales', value: r.envios_totales, icon: 'cube', tone: 'purple', delta: '12%', trend: 'up', note: 'vs ayer' },
    { label: 'En tránsito', value: r.en_transito, icon: 'truck', tone: 'orange', delta: '5%', trend: 'up', note: 'vs ayer' },
    { label: 'Entregados', value: r.entregados, icon: 'check', tone: 'green', delta: '18%', trend: 'up', note: 'vs ayer' },
    { label: 'Pendientes', value: r.pendientes, icon: 'clock', tone: 'red', delta: '8%', trend: 'down', note: 'vs ayer' },
    { label: 'Viajes activos', value: `${r.viajes_activos} / ${r.viajes_total}`, icon: 'users', tone: 'blue', delta: '66%', trend: 'neutral', note: 'disponibles' },
    { label: 'Zonas activas', value: r.zonas_activas, icon: 'flag', tone: 'purple', delta: '', trend: 'neutral', note: 'Ver detalle' },
  ];
  document.getElementById('summaryCards').innerHTML = cards.map(c => `
    <div class="card">
      <div class="card-top">
        <span class="card-icon card-icon-${c.tone}">${iconSVG(c.icon)}</span>
        <span class="label">${c.label}</span>
      </div>
      <span class="value">${c.value}</span>
      ${c.delta ? `<span class="delta ${c.trend}">↑ ${c.delta} <small>${c.note}</small></span>` : `<span class="delta neutral detail-link">${c.note}</span>`}
    </div>
  `).join('');
}

let map, markersLayer;
function estadoColor(estado) {
  if (estado === 'en_ruta') return '#1fa971';
  if (estado === 'detenido') return '#2f6fed';
  return '#8b5cf6';
}

async function renderMonitoreo() {
  const viajes = await getJSON('monitoreo.php');

  document.getElementById('viajeList').innerHTML = viajes.map(v => `
    <li>
      <span class="monitor-icon monitor-${v.estado}">${iconSVG('bike')}</span>
      <div class="monitor-info">
        <span class="nombre">${v.vehiculo} · ${v.conductor}</span>
        <span class="zona">${v.zona}${v.estado === 'sin_conexion' ? ` · Último reporte: ${new Date(v.ultimo_reporte).toLocaleTimeString('es-NI', { hour: '2-digit', minute: '2-digit' })}` : ''}</span>
      </div>
      <span class="estado-tag estado-${v.estado}">${ESTADO_LABEL[v.estado]}</span>
    </li>
  `).join('');

  if (!map) {
    map = L.map('map').setView([viajes[0]?.lat || 12.136, viajes[0]?.lng || -86.25], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);
    markersLayer = L.layerGroup().addTo(map);
  }
  markersLayer.clearLayers();

  viajes.forEach(v => {
    const marker = L.circleMarker([v.lat, v.lng], {
      radius: 9,
      color: '#fff',
      weight: 2,
      fillColor: estadoColor(v.estado),
      fillOpacity: 1,
    }).addTo(markersLayer);

    let popup = `<strong>${v.vehiculo}</strong> · ${v.conductor}<br>${ESTADO_LABEL[v.estado]} · ${v.zona}`;
    if (v.estado === 'sin_conexion') {
      popup += `<br><small>Último reporte: ${new Date(v.ultimo_reporte).toLocaleTimeString('es-NI')}</small>`;
    }
    marker.bindPopup(popup);
  });
}

async function renderZonas() {
  const zonas = await getJSON('zonas.php');
  document.getElementById('zonaBars').innerHTML = zonas.map(z => `
    <li>
      <div class="zona-top"><span>${z.nombre}</span><span>${Number(z.cumplimiento_pct).toFixed(0)}%</span></div>
      <div class="bar-bg"><div class="bar-fill" style="width:${z.cumplimiento_pct}%"></div></div>
    </li>
  `).join('');
}

async function renderAlertas() {
  const alertas = await getJSON('alertas.php');
  document.getElementById('alertPill').textContent = alertas.length;
  document.getElementById('alertCount').textContent = alertas.length;

  document.getElementById('alertasBody').innerHTML = alertas.map(a => `
    <tr>
      <td data-label="Tipo"><span class="tipo-tag">${TIPO_ICONO[a.tipo] || '•'} ${TIPO_LABEL[a.tipo] || a.tipo}</span></td>
      <td data-label="Descripción">${a.descripcion}</td>
      <td data-label="Relacionado con">${a.relacionado_con}</td>
      <td data-label="Zona">${a.zona}</td>
      <td data-label="Hora">${new Date(a.hora).toLocaleTimeString('es-NI', { hour: '2-digit', minute: '2-digit' })}</td>
      <td data-label="">👁️</td>
    </tr>
  `).join('');
}

async function renderActividades() {
  const actividades = await getJSON('actividades.php');
  const iconos = { completado: '✅', asignado: '📌', incidencia: '⚠️' };
  document.getElementById('actividadList').innerHTML = actividades.map(a => `
    <li>
      <span>${iconos[a.tipo] || '•'} ${a.descripcion}</span>
      <span class="tiempo">${timeAgo(a.creado_en)}</span>
    </li>
  `).join('');
}

async function refreshAll() {
  try {
    await Promise.all([
      renderResumen(),
      renderMonitoreo(),
      renderZonas(),
      renderAlertas(),
      renderActividades(),
    ]);
  } catch (err) {
    console.error(err);
  }
}

refreshAll();
const sideNav = document.getElementById('sideNav');
const navOverlay = document.getElementById('navOverlay');
const menuBtn = document.getElementById('menuBtn');
const closeNavBtn = document.getElementById('closeNavBtn');

function abrirMenu() {
  sideNav.classList.add('open');
  navOverlay.classList.add('open');
  menuBtn.setAttribute('aria-expanded', 'true');
}
function cerrarMenu() {
  sideNav.classList.remove('open');
  navOverlay.classList.remove('open');
  menuBtn.setAttribute('aria-expanded', 'false');
}

menuBtn.addEventListener('click', () => {
  sideNav.classList.contains('open') ? cerrarMenu() : abrirMenu();
});
closeNavBtn.addEventListener('click', cerrarMenu);
navOverlay.addEventListener('click', cerrarMenu);
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarMenu(); });
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', cerrarMenu);
});

let resizeTimeout;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(() => { if (map) map.invalidateSize(); }, 200);
});
sideNav.addEventListener('transitionend', () => { if (map) map.invalidateSize(); });
const modalBackdrop = document.getElementById('modalBackdrop');
const modalTitle = document.getElementById('modalTitle');
const modalContent = document.getElementById('modalContent');
const modalClose = document.getElementById('modalClose');
const toast = document.getElementById('toast');

function escapeHTML(value) {
  return String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
}
function showModal(title, html) {
  modalTitle.textContent = title;
  modalContent.innerHTML = html;
  modalBackdrop.classList.add('open');
  modalBackdrop.setAttribute('aria-hidden','false');
}
function closeModal() {
  modalBackdrop.classList.remove('open');
  modalBackdrop.setAttribute('aria-hidden','true');
}
function showToast(message) {
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.classList.remove('show'), 2600);
}
modalClose.addEventListener('click', closeModal);
modalBackdrop.addEventListener('click', e => { if (e.target === modalBackdrop) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
const notificationsBtn = document.getElementById('notificationsBtn');
notificationsBtn.addEventListener('click', async () => {
  const alertas = await getJSON('alertas.php');
  showModal('Notificaciones', alertas.length ? `<ul class="modal-list">${alertas.map(a => `<li><strong>${escapeHTML(TIPO_LABEL[a.tipo] || a.tipo)}</strong><br>${escapeHTML(a.descripcion)}<br><small>${escapeHTML(a.zona)} · ${timeAgo(a.hora)}</small></li>`).join('')}</ul>` : '<p>No hay notificaciones pendientes.</p>');
});
document.getElementById('exportBtn').addEventListener('click', async () => {
  const [resumen, viajes, zonas, alertas, actividades] = await Promise.all([
    getJSON('resumen.php'), getJSON('monitoreo.php'), getJSON('zonas.php'), getJSON('alertas.php'), getJSON('actividades.php')
  ]);
  const rows = [['SECCIÓN','CAMPO','VALOR'],
    ['Resumen','Envíos totales',resumen.envios_totales],['Resumen','En tránsito',resumen.en_transito],['Resumen','Entregados',resumen.entregados],['Resumen','Pendientes',resumen.pendientes],['Resumen','Viajes activos',`${resumen.viajes_activos}/${resumen.viajes_total}`],['Resumen','Zonas activas',resumen.zonas_activas],
    ...viajes.map(v => ['Monitoreo', `${v.vehiculo} · ${v.conductor}`, `${ESTADO_LABEL[v.estado]} · ${v.zona}`]),
    ...zonas.map(z => ['Zona', z.nombre, `${Number(z.cumplimiento_pct).toFixed(0)}%`]),
    ...alertas.map(a => ['Alerta', TIPO_LABEL[a.tipo] || a.tipo, a.descripcion]),
    ...actividades.map(a => ['Actividad', a.tipo, a.descripcion])
  ];
  const csv = '\ufeff' + rows.map(row => row.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(',')).join('\r\n');
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href=url; a.download=`reporte-supervisor-${new Date().toISOString().slice(0,10)}.csv`; a.click();
  URL.revokeObjectURL(url);
  showToast('Reporte exportado correctamente.');
});
const closeFullscreenMap = document.getElementById('closeFullscreenMap');
document.getElementById('verMapaCompleto').addEventListener('click', e => {
  e.preventDefault();
  const mapEl = document.getElementById('map');
  mapEl.classList.add('map-fullscreen');
  closeFullscreenMap.classList.add('open');
  setTimeout(() => { if (map) { map.invalidateSize(); const points = Object.values(markersLayer?._layers || {}).map(m => m.getLatLng ? m.getLatLng() : null).filter(Boolean); if(points.length) map.fitBounds(points, {padding:[30,30]}); } }, 100);
});
closeFullscreenMap.addEventListener('click', () => {
  document.getElementById('map').classList.remove('map-fullscreen');
  closeFullscreenMap.classList.remove('open');
  setTimeout(() => map && map.invalidateSize(), 100);
});
const cumplimientoRango = document.getElementById('cumplimientoRango');
cumplimientoRango.addEventListener('change', async () => {
  const zonas = await getJSON('zonas.php');
  const factor = cumplimientoRango.value === 'Esta semana' ? 1.02 : 1;
  document.getElementById('zonaBars').innerHTML = zonas.map(z => {
    const pct = Math.min(100, Number(z.cumplimiento_pct) * factor);
    return `<li><div class="zona-top"><span>${escapeHTML(z.nombre)}</span><span>${pct.toFixed(0)}%</span></div><div class="bar-bg"><div class="bar-fill" style="width:${pct}%"></div></div></li>`;
  }).join('');
});
async function abrirDetalleAlerta(index) {
  const alertas = await getJSON('alertas.php');
  const a = alertas[index]; if (!a) return;
  showModal(TIPO_LABEL[a.tipo] || 'Alerta', `<p><strong>Descripción:</strong> ${escapeHTML(a.descripcion)}</p><p><strong>Relacionado con:</strong> ${escapeHTML(a.relacionado_con)}</p><p><strong>Zona:</strong> ${escapeHTML(a.zona)}</p><p><strong>Hora:</strong> ${new Date(a.hora).toLocaleTimeString('es-NI',{hour:'2-digit',minute:'2-digit'})}</p>`);
}
const oldRenderAlertas = renderAlertas;
renderAlertas = async function() {
  await oldRenderAlertas();
  document.querySelectorAll('#alertasBody tr').forEach((row, i) => {
    row.classList.add('alert-row-click');
    row.addEventListener('click', () => abrirDetalleAlerta(i));
  });
};
async function abrirTodasAlertas() {
  const alertas = await getJSON('alertas.php');
  showModal('Todas las alertas', `<ul class="modal-list">${alertas.map((a,i) => `<li><a href="#" data-alert-index="${i}"><strong>${escapeHTML(TIPO_LABEL[a.tipo] || a.tipo)}</strong></a><br>${escapeHTML(a.descripcion)}<br><small>${escapeHTML(a.zona)} · ${timeAgo(a.hora)}</small></li>`).join('')}</ul>`);
  modalContent.querySelectorAll('[data-alert-index]').forEach(el => el.addEventListener('click', e => { e.preventDefault(); abrirDetalleAlerta(Number(el.dataset.alertIndex)); }));
}
async function abrirTodasActividades() {
  const actividades = await getJSON('actividades.php');
  showModal('Todas las actividades', `<ul class="modal-list">${actividades.map(a => `<li>${escapeHTML(a.descripcion)}<br><small>${escapeHTML(a.tipo)} · ${timeAgo(a.creado_en)}</small></li>`).join('')}</ul>`);
}
document.getElementById('verTodasAlertas').addEventListener('click', e => { e.preventDefault(); abrirTodasAlertas(); });
document.getElementById('verTodasActividades').addEventListener('click', e => { e.preventDefault(); abrirTodasActividades(); });
Promise.resolve(getJSON('alertas.php')).then(alertas => {
  if (alertas.length) document.getElementById('alertCount').classList.add('has-alerts');
});
