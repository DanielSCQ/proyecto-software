/*=================================
    FECHA Y HORA DEL DASHBOARD
=================================*/

function actualizarFechaHora() {

    const ahora = new Date();

    const fecha = ahora.toLocaleDateString("es-CO", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
    });

    const hora = ahora.toLocaleTimeString("es-CO");

    document.getElementById("fecha").textContent = fecha;
    document.getElementById("hora").textContent = hora;

}

actualizarFechaHora();

setInterval(actualizarFechaHora, 1000);