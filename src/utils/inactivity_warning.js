// Tiempos en milisegundos
const WARNING_TIME = 3 * 60 * 1000; // 3 minutos
const AUTO_LOGOUT_TIME = 30 * 1000; // 30 segundos después de la advertencia
const CHECK_INTERVAL = 5000; // Revisar cada minuto

let warningTimer;
let logoutTimer;
let lastActivity = Date.now();

// Función para mostrar la advertencia
function showWarning() {
    // Crear el modal de advertencia
    const modal = document.createElement('div');
    modal.id = 'inactivity-warning';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0,0,0,0.7)';
    modal.style.display = 'flex';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.zIndex = '1000';
    
    // Contenido del modal
    modal.innerHTML = `
        <div style="background: white; padding: 20px; border-radius: 5px; text-align: center;">
            <h2>¡Advertencia de inactividad!</h2>
            <p>Tu sesión está a punto de expirar debido a inactividad.</p>
            <p>¿Deseas continuar con la sesión?</p>
            <button id="extend-session" style="margin: 10px; padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Sí, continuar</button>
            <button id="logout-now" style="margin: 10px; padding: 8px 15px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Cerrar sesión</button>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Configurar botones
    document.getElementById('extend-session').addEventListener('click', extendSession);
    document.getElementById('logout-now').addEventListener('click', logoutNow);
    
    // Configurar temporizador para cierre automático
    logoutTimer = setTimeout(logoutNow, AUTO_LOGOUT_TIME);
}

// Función para extender la sesión
function extendSession() {
    clearTimeout(logoutTimer);
    document.getElementById('inactivity-warning').remove();
    
    // Enviar solicitud al servidor para extender la sesión
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'extend_session=true'
    });
    
    // Reiniciar el temporizador
    startInactivityTimer();
}

// Función para cerrar sesión
function logoutNow() {
    window.location.href = '/src/utils/logout.php';
}

// Función para reiniciar el temporizador
function startInactivityTimer() {
    clearTimeout(warningTimer);
    warningTimer = setTimeout(showWarning, WARNING_TIME);
}

// Detectar actividad del usuario
function resetInactivityTimer() {
    lastActivity = Date.now();
}

// Eventos para detectar actividad
['mousemove', 'keypress', 'click', 'scroll'].forEach(event => {
    document.addEventListener(event, resetInactivityTimer, {passive: true});
});

// Iniciar el temporizador cuando la página carga
document.addEventListener('DOMContentLoaded', () => {
    startInactivityTimer();
    
    // Verificar periódicamente si el servidor ha marcado la sesión como inactiva
    setInterval(() => {
        fetch(window.location.href, {
            method: 'HEAD',
            cache: 'no-store'
        }).then(response => {
            if (response.redirected && response.url.includes('login.php?expired=1')) {
                logoutNow();
            }
        });
    }, CHECK_INTERVAL);
});