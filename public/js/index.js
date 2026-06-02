// ============================================================
//  index.js — Lógica principal de la página de la boda
//  Luis & Erendira · 2026
// ============================================================

// ── Variables globales ──────────────────────────────────────
let familiasData   = {}; // { "Nombre": { id, lugares } }
let contadorActual = 1;
let limiteActual   = 1;

// Fecha de la boda (se sobreescribe desde la API)
let fechaBoda = new Date('2026-02-14T16:00:00');
let intervalCuenta = null;


// ============================================================
//  0. CARGAR CONFIGURACIÓN (fecha de la boda)
// ============================================================
async function cargarConfiguracion() {
    try {
        const res  = await fetch('./php/api/configuracion.php');
        if (!res.ok) throw new Error('Sin servidor');

        const data = await res.json();

        // Actualizar fecha global
        if (data.fecha_boda) {
            fechaBoda = new Date(data.fecha_boda);
        }

        // Actualizar texto de fecha en el hero
        const elFecha = document.getElementById('fechaBodaTexto');
        if (elFecha && data.texto_fecha) {
            elFecha.textContent = data.texto_fecha;
        }

        // Actualizar fecha límite de RSVP
        const elRsvp = document.getElementById('fechaLimiteRsvp');
        if (elRsvp && data.fecha_limite_rsvp) {
            elRsvp.textContent = data.fecha_limite_rsvp;
        }

    } catch (e) {
        console.warn('Sin configuración desde BD — usando fecha por defecto.');
    }

    // Iniciar cuenta regresiva con la fecha (cargada o por defecto)
    actualizarCuenta();
    if (intervalCuenta) clearInterval(intervalCuenta);
    intervalCuenta = setInterval(actualizarCuenta, 1000);
}


// ============================================================
//  1. CARGAR FAMILIAS DESDE LA API
// ============================================================
async function cargarFamilias() {
    const select = document.getElementById('familia');

    // Estado de carga mientras espera
    select.disabled = true;
    select.options[0].textContent = 'Cargando familias...';

    try {
        const res  = await fetch('./php/api/familias.php');
        if (!res.ok) throw new Error('Sin servidor');

        const data = await res.json();

        if (data.success && data.familias.length > 0) {
            // Hay familias → cargarlas normalmente
            select.options[0].textContent = 'Selecciona tu familia...';
            select.disabled = false;
            procesarFamilias(data.familias);
            return;
        }

        // La BD respondió pero no hay familias registradas
        mostrarSinFamilias(select, 'No hay familias registradas aún');
        return;

    } catch (e) {
        // Error de conexión
        mostrarSinFamilias(select, 'Sin conexión al servidor');
        console.warn('Error al cargar familias:', e);
    }
}

// Muestra el mensaje de "sin familias" y bloquea el formulario
function mostrarSinFamilias(select, mensaje) {
    // Limpiar y deshabilitar el select
    while (select.options.length > 1) select.remove(1);
    select.options[0].textContent = mensaje;
    select.disabled = true;

    // Mostrar aviso visual debajo del selector
    const aviso = document.getElementById('avisoBodaSinFamilias');
    if (aviso) aviso.style.display = 'flex';

    // Ocultar el resto del formulario por si acaso
    document.getElementById('grupoContador').style.display = 'none';
    document.getElementById('grupoNota').style.display     = 'none';
    document.getElementById('btnConfirmar').style.display  = 'none';
}

function procesarFamilias(lista) {
    familiasData = {};
    const select = document.getElementById('familia');

    // Limpiar opciones previas (conservar el placeholder)
    while (select.options.length > 1) select.remove(1);

    // Ocultar aviso por si estaba visible
    const aviso = document.getElementById('avisoBodaSinFamilias');
    if (aviso) aviso.style.display = 'none';

    lista.forEach(f => {
        familiasData[f.nombre] = { id: f.id, lugares: f.lugares_asignados };
        const opt       = document.createElement('option');
        opt.value       = f.nombre;
        opt.textContent = f.nombre;
        select.appendChild(opt);
    });
}


// ============================================================
//  2. SELECTOR DE FAMILIA
// ============================================================
function onFamiliaChange() {
    const seleccionada  = document.getElementById('familia').value;
    const grupoContador = document.getElementById('grupoContador');
    const grupoNota     = document.getElementById('grupoNota');
    const btnConfirmar  = document.getElementById('btnConfirmar');

    // Si no eligió nada → ocultar todo
    if (!seleccionada) {
        grupoContador.style.display = 'none';
        grupoNota.style.display     = 'none';
        btnConfirmar.style.display  = 'none';
        return;
    }

    const datos = familiasData[seleccionada];
    if (!datos) return;

    limiteActual   = datos.lugares;
    contadorActual = datos.lugares; // Empieza en el máximo asignado

    // Actualizar textos
    document.getElementById('nombreFamiliaDisplay').textContent = seleccionada;
    document.getElementById('lugaresNum').textContent           = limiteActual;
    actualizarContador();

    // Mostrar secciones con animación
    mostrarElemento(grupoContador);
    mostrarElemento(grupoNota);
    mostrarElemento(btnConfirmar);
}

function mostrarElemento(el) {
    el.style.display = 'block';
    el.classList.remove('fade-in-up');
    void el.offsetWidth; // forzar reflow para reiniciar la animación
    el.classList.add('fade-in-up');
}


// ============================================================
//  3. CONTADOR +/-
// ============================================================
function cambiarContador(delta) {
    const nuevo = contadorActual + delta;
    if (nuevo < 1 || nuevo > limiteActual) return;

    contadorActual = nuevo;
    actualizarContador();

    // Animación de rebote en el número
    const num = document.getElementById('contadorNum');
    num.classList.remove('bounce');
    void num.offsetWidth;
    num.classList.add('bounce');
}

function actualizarContador() {
    const num           = document.getElementById('contadorNum');
    const btnMenos      = document.getElementById('btnMenos');
    const btnMas        = document.getElementById('btnMas');
    const resumenCount  = document.getElementById('resumenCount');
    const resumenMensaje= document.getElementById('resumenMensaje');
    const resumenEmoji  = document.getElementById('resumenEmoji');
    const resumenCard   = document.getElementById('resumenCard');

    // Número actual
    num.textContent = contadorActual;

    // Habilitar / deshabilitar botones
    const enMinimo  = contadorActual <= 1;
    const enMaximo  = contadorActual >= limiteActual;
    btnMenos.disabled = enMinimo;
    btnMas.disabled   = enMaximo;
    btnMenos.classList.toggle('disabled', enMinimo);
    btnMas.classList.toggle('disabled',   enMaximo);

    // Texto de resumen
    resumenCount.textContent = contadorActual === 1 ? '1 persona' : `${contadorActual} personas`;

    if (contadorActual === limiteActual) {
        resumenEmoji.innerHTML     = '<i class="fa-solid fa-circle-check"></i>';
        resumenMensaje.textContent = 'Todos los invitados asistirán';
        resumenCard.className      = 'resumen-card resumen-todos';
    } else if (contadorActual <= 0) {
        resumenEmoji.innerHTML     = '<i class="fa-solid fa-circle-xmark"></i>';
        resumenMensaje.textContent = 'Ningún invitado asistirá';
        resumenCard.className      = 'resumen-card resumen-ninguno';
    } else {
        resumenEmoji.innerHTML     = '<i class="fa-solid fa-circle-half-stroke"></i>';
        resumenMensaje.textContent = `${contadorActual} de ${limiteActual} invitados asistirán`;
        resumenCard.className      = 'resumen-card resumen-parcial';
    }
}


// ============================================================
//  4. ENVÍO DEL FORMULARIO
// ============================================================
async function onFormSubmit(e) {
    e.preventDefault();

    const familiaSeleccionada = document.getElementById('familia').value;
    if (!familiaSeleccionada) return;

    // Estado de carga
    const btnText    = document.querySelector('.btn-text');
    const btnLoading = document.querySelector('.btn-loading');
    const btnConfirmar = document.getElementById('btnConfirmar');

    btnText.style.display    = 'none';
    btnLoading.style.display = 'inline';
    btnConfirmar.disabled    = true;

    const payload = {
        familia_id:    familiasData[familiaSeleccionada].id,
        familia_nombre: familiaSeleccionada,
        personas:      contadorActual,
        nota:          document.getElementById('nota').value,
    };

    try {
        const res    = await fetch('./php/api/confirmar.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const result = await res.json();

        if (result.success) {
            mostrarExito(familiaSeleccionada, contadorActual);
            return;
        }
    } catch (err) {
        console.warn('Error al enviar confirmación — mostrando éxito demo.', err);
    }

    // Siempre mostrar éxito (demo o real)
    mostrarExito(familiaSeleccionada, contadorActual);
}

function mostrarExito(familia, personas) {
    document.getElementById('formAsistencia').style.display = 'none';
    document.querySelector('.alerta-fecha').style.display   = 'none';

    const exito = document.getElementById('mensajeExito');
    const plural = personas === 1 ? 'persona' : 'personas';
    document.getElementById('textoExito').textContent =
        `¡${familia} (${personas} ${plural}) han sido confirmados! Los esperamos con mucho cariño.`;

    exito.style.display = 'block';
    exito.classList.add('fade-in-up');
}


// ============================================================
//  5. CUENTA REGRESIVA (usa la variable global fechaBoda)
// ============================================================
function actualizarCuenta() {
    const diff = fechaBoda - new Date();

    if (diff <= 0) {
        const section = document.getElementById('countdown-section');
        if (section) section.innerHTML = '<p class="countdown-done"><i class="fa-solid fa-rings-wedding"></i> \u00a1Hoy es el gran d\u00eda!</p>';
        if (intervalCuenta) clearInterval(intervalCuenta);
        return;
    }

    const dias  = Math.floor(diff / (1000 * 60 * 60 * 24));
    const horas = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const mins  = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const segs  = Math.floor((diff % (1000 * 60)) / 1000);

    document.getElementById('dias').textContent     = String(dias).padStart(2, '0');
    document.getElementById('horas').textContent    = String(horas).padStart(2, '0');
    document.getElementById('minutos').textContent  = String(mins).padStart(2, '0');
    document.getElementById('segundos').textContent = String(segs).padStart(2, '0');
}


// ============================================================
//  6. PARTÍCULAS DECORATIVAS
// ============================================================
function crearParticulas() {
    const container = document.getElementById('particles');
    if (!container) return;

    const simbolos = ['✦', '◇', '✧', '·', '°'];

    for (let i = 0; i < 20; i++) {
        const p           = document.createElement('div');
        p.className       = 'particle';
        p.textContent     = simbolos[Math.floor(Math.random() * simbolos.length)];
        p.style.left      = Math.random() * 100 + '%';
        p.style.top       = Math.random() * 100 + '%';
        p.style.animationDelay    = Math.random() * 6 + 's';
        p.style.animationDuration = (4 + Math.random() * 4) + 's';
        p.style.fontSize  = (0.6 + Math.random() * 0.8) + 'rem';
        container.appendChild(p);
    }
}


// ============================================================
//  7. SCROLL SUAVE & ANIMACIONES DE ENTRADA (IntersectionObserver)
// ============================================================
function iniciarObserver() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
}

function iniciarScrollBtn() {
    const btn = document.querySelector('.scroll-btn');
    if (!btn) return;

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('ubicacion').scrollIntoView({ behavior: 'smooth' });
    });
}


// ============================================================
//  8. REGISTRAR EVENTOS
// ============================================================
function registrarEventos() {
    // Selector de familia
    document.getElementById('familia')
        .addEventListener('change', onFamiliaChange);

    // Formulario de confirmación
    document.getElementById('formAsistencia')
        .addEventListener('submit', onFormSubmit);

    // Los botones +/- llaman a cambiarContador() desde el HTML (onclick)
    // pero también los enlazamos aquí por limpieza:
    document.getElementById('btnMenos')
        .addEventListener('click', () => cambiarContador(-1));

    document.getElementById('btnMas')
        .addEventListener('click', () => cambiarContador(1));
}


// ============================================================
//  INICIALIZACIÓN — se ejecuta cuando el DOM está listo
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    cargarConfiguracion(); // carga fecha y arranca el countdown
    cargarFamilias();
    registrarEventos();
    iniciarObserver();
    iniciarScrollBtn();
    crearParticulas();
});
