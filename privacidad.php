<?php
$pageTitle = 'Política de privacidad';
$pageDesc = 'Cómo el Gimnasio Castillo Americano trata los datos personales de estudiantes, familias y personal (Ley 1581 de 2012) y qué cookies usa el sitio.';

// Datos del responsable. Si se completan el NIT, aparece en la página.
$responsable      = 'Gimnasio Castillo Americano';
$responsable_nit  = '';
$responsable_dir  = 'Calle 3B #23-13, Barrio Callejas II, Valledupar, Cesar, Colombia';
$responsable_mail = 'gimnasiocastilloamericano@gmail.com';
$responsable_tel  = '321 654 8235';
$vigente_desde    = '29 de octubre de 2026';

include "header.php";
?>

<div class="hm-wrap">

    <section class="hm-page-hero">
        <span class="hm-ph-glow"></span>
        <div class="hm-ph-inner" data-aos="fade-down">
            <h1 class="hm-ph-title"><span class="grad">Política de privacidad</span></h1>
            <p class="hm-ph-sub">Qué datos guardamos, para qué los usamos y cómo puedes consultarlos, corregirlos o pedir que se borren.</p>
        </div>
    </section>

    <section class="hm-section">
        <div class="container">
            <div class="hm-legal">

                <nav class="hm-legal-indice" aria-label="Contenido de la política">
                    <span class="hm-kicker">Contenido</span>
                    <ol>
                        <li><a href="#responsable">Quién es el responsable</a></li>
                        <li><a href="#datos">Qué datos tratamos</a></li>
                        <li><a href="#finalidad">Para qué los usamos</a></li>
                        <li><a href="#menores">Datos de niños, niñas y adolescentes</a></li>
                        <li><a href="#acceso">Quién puede verlos</a></li>
                        <li><a href="#seguridad">Cómo los protegemos</a></li>
                        <li><a href="#cookies">Cookies</a></li>
                        <li><a href="#derechos">Tus derechos y cómo ejercerlos</a></li>
                        <li><a href="#conservacion">Cuánto tiempo los guardamos</a></li>
                        <li><a href="#cambios">Cambios a esta política</a></li>
                    </ol>
                </nav>

                <div class="hm-legal-cuerpo">

                    <p class="hm-legal-intro">
                        En el <?= $responsable ?> cuidamos la información de nuestros estudiantes, sus familias y nuestro
                        personal. Esta política explica cómo tratamos esos datos, conforme a la Ley 1581 de 2012, el
                        Decreto 1377 de 2013 (compilado en el Decreto 1074 de 2015) y demás normas colombianas de
                        protección de datos personales.
                    </p>

                    <h2 id="responsable">1. Quién es el responsable</h2>
                    <ul class="hm-legal-datos">
                        <li><strong>Responsable:</strong> <?= $responsable ?></li>
                        <?php if ($responsable_nit !== ''): ?>
                        <li><strong>NIT:</strong> <?= htmlspecialchars($responsable_nit) ?></li>
                        <?php endif; ?>
                        <li><strong>Dirección:</strong> <?= $responsable_dir ?></li>
                        <li><strong>Correo para temas de datos:</strong> <a href="mailto:<?= $responsable_mail ?>"><?= $responsable_mail ?></a></li>
                        <li><strong>Teléfono:</strong> <a href="tel:+57<?= str_replace(' ', '', $responsable_tel) ?>"><?= $responsable_tel ?></a></li>
                    </ul>

                    <h2 id="datos">2. Qué datos tratamos</h2>
                    <ul>
                        <li><strong>Estudiantes:</strong> nombre, documento de identidad, fecha de nacimiento, curso, calificaciones, desempeños, observaciones académicas y boletines.</li>
                        <li><strong>Padres, madres y acudientes:</strong> nombre, correo electrónico, parentesco y el vínculo con cada estudiante a su cargo.</li>
                        <li><strong>Docentes y personal:</strong> nombre, correo electrónico, cursos y asignaturas a cargo. Si el colegio lo publica, también foto, cargo y una breve reseña en la sección Docentes.</li>
                        <li><strong>Formulario de contacto:</strong> nombre, correo, teléfono (opcional) y el mensaje. Se envía al correo del colegio y no se guarda en la plataforma.</li>
                        <li><strong>Seguridad:</strong> la dirección IP y el correo usados en los intentos de ingreso, para frenar ataques. Se borran automáticamente en un día.</li>
                    </ul>
                    <p>No pedimos datos sensibles distintos a los académicos, ni datos bancarios, en esta plataforma.</p>

                    <h2 id="finalidad">3. Para qué los usamos</h2>
                    <ul>
                        <li>Registrar calificaciones, generar boletines y llevar el seguimiento académico de cada estudiante.</li>
                        <li>Que cada familia consulte en línea el avance y los boletines de sus hijos.</li>
                        <li>Avisar a las familias y al personal sobre boletines publicados, alertas académicas y novedades del colegio.</li>
                        <li>Enviar correos de la plataforma, como la recuperación de contraseña.</li>
                        <li>Responder los mensajes que nos escriben por el formulario de contacto.</li>
                        <li>Cumplir las obligaciones legales del colegio ante las autoridades educativas.</li>
                    </ul>
                    <p>No vendemos ni cedemos datos personales a terceros con fines comerciales o publicitarios.</p>

                    <h2 id="menores">4. Datos de niños, niñas y adolescentes</h2>
                    <p>
                        Los datos de los estudiantes se tratan respetando su interés superior y sus derechos fundamentales,
                        como exige el artículo 7 de la Ley 1581. Solo se usan para fines educativos, y la información de
                        cada estudiante la ven únicamente sus acudientes registrados, sus docentes y la administración
                        del colegio.
                    </p>

                    <h2 id="acceso">5. Quién puede verlos</h2>
                    <ul>
                        <li><strong>Familias:</strong> solo la información de los estudiantes a su cargo.</li>
                        <li><strong>Docentes:</strong> las notas de los cursos y asignaturas que dictan. Los directores de grupo, además, los boletines de su curso.</li>
                        <li><strong>Administración del colegio:</strong> la información necesaria para gestionar la institución.</li>
                    </ul>
                    <p>
                        Para funcionar, la plataforma usa proveedores tecnológicos que guardan o transmiten los datos por
                        cuenta del colegio, sin poder usarlos para fines propios: <strong>Neon</strong> (base de datos),
                        <strong>Railway</strong> y <strong>Vercel</strong> (servidores del sitio) y <strong>Brevo</strong>
                        (envío de correos). Sus servidores están fuera de Colombia, principalmente en Estados Unidos, por lo
                        que hay una transmisión internacional de datos con las garantías que exige la ley.
                    </p>

                    <h2 id="seguridad">6. Cómo los protegemos</h2>
                    <ul>
                        <li>Toda la comunicación con el sitio va cifrada (HTTPS).</li>
                        <li>Las contraseñas se guardan cifradas: nadie, ni el colegio, puede leerlas.</li>
                        <li>Cada persona entra con su propia cuenta y solo ve lo que le corresponde según su rol.</li>
                        <li>Los boletines en PDF no son públicos: solo los descarga quien tiene permiso.</li>
                        <li>La base de datos tiene permisos restringidos y límites contra intentos repetidos.</li>
                    </ul>

                    <h2 id="cookies">7. Cookies</h2>
                    <p>Este sitio usa muy pocas cookies, y ninguna para publicidad ni para seguir tu navegación.</p>
                    <div class="table-responsive">
                        <table class="hm-legal-tabla">
                            <thead>
                                <tr><th scope="col">Cookie</th><th scope="col">De quién</th><th scope="col">Para qué</th><th scope="col">Duración</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>PHPSESSID</code></td>
                                    <td>Del colegio</td>
                                    <td>Mantener abierta tu sesión al entrar a la plataforma. Sin ella no es posible iniciar sesión.</td>
                                    <td>Hasta 2 horas</td>
                                </tr>
                                <tr>
                                    <td>Cookies de Google</td>
                                    <td>Google</td>
                                    <td>Las pone el mapa de la página Contacto cuando se muestra.</td>
                                    <td>Las define Google</td>
                                </tr>
                                <tr>
                                    <td>Cookies de YouTube</td>
                                    <td>Google</td>
                                    <td>Solo si reproduces un video en la página Docentes (usamos el modo de privacidad mejorada de YouTube).</td>
                                    <td>Las define Google</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        Además, el navegador recuerda en tu propio equipo preferencias como el modo oscuro. Esa información
                        no se envía a ningún servidor. Puedes borrar las cookies cuando quieras desde la configuración de tu
                        navegador; si lo haces, tendrás que volver a iniciar sesión.
                    </p>

                    <h2 id="derechos">8. Tus derechos y cómo ejercerlos</h2>
                    <p>Como titular de los datos, o como representante de un estudiante menor de edad, puedes:</p>
                    <ul>
                        <li>Conocer los datos que tenemos y saber cómo se han usado.</li>
                        <li>Actualizarlos y corregirlos.</li>
                        <li>Pedir prueba de la autorización que nos diste.</li>
                        <li>Revocar la autorización o pedir que se borren, cuando no exista un deber legal o contractual de conservarlos.</li>
                        <li>Presentar quejas ante la Superintendencia de Industria y Comercio (SIC), después de haber acudido primero al colegio.</li>
                    </ul>
                    <p>
                        Escríbenos a <a href="mailto:<?= $responsable_mail ?>"><?= $responsable_mail ?></a> o acércate a la
                        secretaría, indicando tu nombre, documento, qué solicitas y cómo contactarte. Respondemos las
                        <strong>consultas</strong> en máximo 10 días hábiles y los <strong>reclamos</strong> (corrección,
                        actualización o supresión) en máximo 15 días hábiles, como fija la ley.
                    </p>

                    <h2 id="conservacion">9. Cuánto tiempo los guardamos</h2>
                    <p>
                        Los datos académicos se conservan mientras el estudiante esté vinculado al colegio y, después, el
                        tiempo que exijan las normas de archivo escolar para expedir certificados y constancias. Los
                        registros de seguridad se borran en un día. Las cuentas de la plataforma se desactivan cuando la
                        persona deja de tener vínculo con el colegio.
                    </p>

                    <h2 id="cambios">10. Cambios a esta política</h2>
                    <p>
                        Si cambiamos esta política lo publicaremos en esta misma página. Los cambios importantes se
                        avisarán a las familias por los canales del colegio.
                    </p>
                    <p class="hm-legal-fecha">Vigente desde el <?= $vigente_desde ?>.</p>

                </div>
            </div>
        </div>
    </section>

</div>

<?php include "footer.php"; ?>
