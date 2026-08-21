<?php
// Pie de página modular
?>
<footer>
    <div class="footer-contenido" style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 20px; text-align: left; padding: 10px 20px;">
        <div style="flex: 1; min-width: 250px;">
            <h3 style="color: white; margin-bottom: 10px;"><?php echo htmlspecialchars($configTienda['nombre_tienda'] ?? 'AGRANDA'); ?></h3>
            <p style="color: #bbb; line-height: 1.6; font-size: 0.95rem;">
                Especialistas en repuestos, partes eléctricas, sistemas hidráulicos y componentes para maquinaria pesada y agrícola.
            </p>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <h4 style="color: white; margin-bottom: 10px;">Atención al Cliente</h4>
            <p style="color: #bbb; font-size: 0.9rem; margin-bottom: 5px;">📞 <?php echo htmlspecialchars($configTienda['telefono_contacto'] ?? '+57 300 000 0000'); ?></p>
            <p style="color: #bbb; font-size: 0.9rem; margin-bottom: 5px;">✉️ <?php echo htmlspecialchars($configTienda['correo_contacto'] ?? 'contacto@agranda.com'); ?></p>
            <p style="color: #bbb; font-size: 0.9rem;">📍 <?php echo htmlspecialchars($configTienda['direccion'] ?? 'Zona Agrícola'); ?></p>
        </div>
        <div style="flex: 1; min-width: 180px;">
            <h4 style="color: white; margin-bottom: 10px;">Enlaces Rápidos</h4>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 6px;"><a href="index.php" style="color: #bbb; text-decoration: none;">Inicio</a></li>
                <li style="margin-bottom: 6px;"><a href="productos.php" style="color: #bbb; text-decoration: none;">Catálogo de Repuestos</a></li>
                <li style="margin-bottom: 6px;"><a href="contacto.php" style="color: #bbb; text-decoration: none;">Atención y PQRs</a></li>
                <li style="margin-bottom: 6px;"><a href="admin/login.php" style="color: orangered; text-decoration: none;">Panel de Administración</a></li>
            </ul>
        </div>
    </div>
    <div style="border-top: 1px solid #333; margin-top: 25px; padding-top: 20px; text-align: center; color: #888; font-size: 0.85rem;">
        <p>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($configTienda['nombre_tienda'] ?? 'AGRANDA'); ?> - Todos los derechos reservados.</p>
    </div>
</footer>

<script src="tienda.js" defer></script>
</body>
</html>
