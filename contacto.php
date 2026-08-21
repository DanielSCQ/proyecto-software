<?php
// contacto.php - Página de Contacto y PQRS AGRANDA (Fase 7)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/config/conexion.php');
require_once(__DIR__ . '/includes/helpers.php');

$configTienda = obtenerConfiguracionTienda($conexion);

// Prefill de asunto si viene por URL
$asuntoPrefill = isset($_GET['asunto']) ? trim($_GET['asunto']) : '';

// Mensajes de sesión
$mensajeExito = $_SESSION['contacto_exito'] ?? null;
$mensajesError = $_SESSION['contacto_errores'] ?? [];
$datosPrevios = $_SESSION['contacto_datos'] ?? [];

// Limpiar mensajes flash de sesión
unset($_SESSION['contacto_exito'], $_SESSION['contacto_errores'], $_SESSION['contacto_datos']);

include('includes/header.php');
?>

<main class="contacto-seccion-principal">
    <div class="contacto-hero">
        <h2>Atención al Cliente y Contacto</h2>
        <p>¿Tienes dudas sobre repuestos, compatibilidad con tu maquinaria o necesitas una cotización? Escríbenos y un especialista técnico de AGRANDA te responderá.</p>
    </div>

    <div class="contacto-layout-grid">
        <!-- Columna Izquierda: Formulario de Contacto / PQR -->
        <section class="contacto-form-card">
            <h3>✉️ Envíanos un Mensaje</h3>

            <?php if (!empty($mensajeExito)): ?>
                <div class="alerta-box alerta-exito">
                    <span class="alerta-icono">✓</span>
                    <div>
                        <strong>¡Mensaje Enviado con Éxito!</strong>
                        <p><?php echo htmlspecialchars($mensajeExito); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensajesError)): ?>
                <div class="alerta-box alerta-error">
                    <span class="alerta-icono">⚠️</span>
                    <div>
                        <strong>Por favor corrige los siguientes errores:</strong>
                        <ul>
                            <?php foreach ($mensajesError as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form action="procesar_contacto.php" method="POST" id="form-contacto-publico" class="contacto-formulario-moderno">
                <div class="form-fila-doble">
                    <div class="form-grupo">
                        <label for="nombre">Nombre Completo <span class="req">*</span></label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               required 
                               placeholder="Ej: Daniel Santiago Gómez"
                               value="<?php echo htmlspecialchars($datosPrevios['nombre'] ?? ''); ?>">
                    </div>

                    <div class="form-grupo">
                        <label for="correo">Correo Electrónico <span class="req">*</span></label>
                        <input type="email" 
                               id="correo" 
                               name="correo" 
                               required 
                               placeholder="Ej: contacto@ejemplo.com"
                               value="<?php echo htmlspecialchars($datosPrevios['correo'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-fila-doble">
                    <div class="form-grupo">
                        <label for="telefono">Teléfono / Celular / WhatsApp</label>
                        <input type="tel" 
                               id="telefono" 
                               name="telefono" 
                               placeholder="Ej: 320 900 7970"
                               value="<?php echo htmlspecialchars($datosPrevios['telefono'] ?? ''); ?>">
                    </div>

                    <div class="form-grupo">
                        <label for="asunto">Asunto <span class="req">*</span></label>
                        <input type="text" 
                               id="asunto" 
                               name="asunto" 
                               required 
                               placeholder="Ej: Cotización de bomba hidráulica o Consulta técnica"
                               value="<?php echo htmlspecialchars(!empty($asuntoPrefill) ? $asuntoPrefill : ($datosPrevios['asunto'] ?? '')); ?>">
                    </div>
                </div>

                <div class="form-grupo">
                    <label for="mensaje">Mensaje o Detalle de la Consulta <span class="req">*</span></label>
                    <textarea id="mensaje" 
                              name="mensaje" 
                              rows="5" 
                              required 
                              placeholder="Cuéntanos el modelo de tu tractor, referencia del repuesto o la consulta que deseas realizar..."><?php echo htmlspecialchars($datosPrevios['mensaje'] ?? ''); ?></textarea>
                </div>

                <div class="form-boton-fila">
                    <button type="submit" class="btn-enviar-contacto">
                        🚀 Enviar Consulta
                    </button>
                </div>
            </form>
        </section>

        <!-- Columna Derecha: Información Institucional -->
        <aside class="contacto-info-card">
            <h3>🏢 Información de la Empresa</h3>
            <p class="institucional-intro">Contamos con punto de atención directa, asesoría técnica especializada y cobertura de despacho en todo el territorio nacional.</p>

            <div class="info-items-lista">
                <div class="info-item-bloque">
                    <div class="info-icono">📍</div>
                    <div class="info-texto">
                        <strong>Dirección Principal:</strong>
                        <p><?php echo htmlspecialchars($configTienda['direccion'] ?? 'Carrera 9 #13-61, Purificación, Tolima'); ?></p>
                    </div>
                </div>

                <div class="info-item-bloque">
                    <div class="info-icono">📞</div>
                    <div class="info-texto">
                        <strong>Teléfono y Atención:</strong>
                        <p><?php echo htmlspecialchars($configTienda['telefono_contacto'] ?? '+57 320 900 7970'); ?></p>
                    </div>
                </div>

                <div class="info-item-bloque">
                    <div class="info-icono">✉️</div>
                    <div class="info-texto">
                        <strong>Correo Electrónico:</strong>
                        <p><?php echo htmlspecialchars($configTienda['correo_contacto'] ?? 'contacto@agranda.com'); ?></p>
                    </div>
                </div>

                <div class="info-item-bloque">
                    <div class="info-icono">⏰</div>
                    <div class="info-texto">
                        <strong>Horario de Atención:</strong>
                        <p>Lunes a Viernes: 7:30 AM - 5:30 PM<br>Sábados: 8:00 AM - 1:00 PM</p>
                    </div>
                </div>
            </div>

            <div class="garantia-badge-box">
                <span class="garantia-icon">🚜</span>
                <div>
                    <strong>Soporte Técnico Especializado</strong>
                    <p>Si no encuentras el repuesto en nuestro catálogo en línea, nuestro equipo te ayuda a localizarlo directamente con fábrica.</p>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include('includes/footer.php'); ?>
