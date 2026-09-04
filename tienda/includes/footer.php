<footer class="site-footer">

    <div class="footer-container">

        <!-- INFORMACIÓN DE AGRANDA -->
        <div class="footer-column footer-about">

            <a href="<?= $base_url ?>index.php" class="footer-brand">
                AGRANDA
            </a>

            <p>
                Repuestos agrícolas de confianza para mantener
                tu maquinaria siempre en funcionamiento.
            </p>

        </div>


        <!-- ENLACES -->
        <div class="footer-column">

            <h3>Enlaces</h3>

            <ul class="footer-links">
                <li>
                    <a href="<?= $base_url ?>index.php">Inicio</a>
                </li>

                <li>
                    <a href="<?= $base_url ?>productos/">Productos</a>
                </li>

                <li>
                    <a href="<?= $base_url ?>nosotros/">Nosotros</a>
                </li>

                <li>
                    <a href="<?= $base_url ?>contacto/">Contacto</a>
                </li>
            </ul>

        </div>


        <!-- CUENTA -->
        <div class="footer-column">

            <h3>Mi cuenta</h3>

            <ul class="footer-links">
                <li>
                    <a href="<?= $base_url ?>cuenta/">
                        Mi cuenta
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url ?>pedidos/">
                        Mis pedidos
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url ?>carrito/">
                        Mi carrito
                    </a>
                </li>
            </ul>

        </div>


        <!-- CONTACTO -->
        <div class="footer-column">

            <h3>¿Necesitas ayuda?</h3>

            <p>
                Si tienes alguna pregunta sobre nuestros productos
                o pedidos, puedes comunicarte con nosotros.
            </p>

            <a href="<?= $base_url ?>contacto/" class="footer-contact-link">
                Contáctanos
            </a>

        </div>

    </div>


    <!-- PARTE INFERIOR -->
    <div class="footer-bottom">

        <div class="footer-bottom-container">

            <p>
                &copy; <?= date("Y") ?> AGRANDA. Todos los derechos reservados.
            </p>

            <p>
                Repuestos agrícolas
            </p>

        </div>

    </div>

</footer>

</body>
</html>