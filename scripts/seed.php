<?php
declare(strict_types=1);

/**
 * seed.php - carga datos de prueba en GeneSysTM (minimo 50 registros por componente).
 *
 * Uso (dentro del contenedor web):
 *     docker compose exec web php /var/www/scripts/seed.php
 * Uso local:
 *     DB_HOST=127.0.0.1 DB_NAME=torneos_db DB_USER=... DB_PASS=... php scripts/seed.php
 *
 * Opciones:
 *     --force   carga aunque ya existan torneos (agrega mas datos)
 *
 * Variables opcionales:
 *     SEED_CLAVE   clave de prueba para todos los usuarios (def: Prueba2026!)
 *
 * Los datos son reproducibles: se usa una semilla fija, asi que dos cargas en bases
 * vacias producen el mismo resultado.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta por linea de comandos.\n");
}

$host = getenv('DB_HOST') ?: '';
$name = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
if ($host === '' || $name === '' || $user === '') {
    fwrite(STDERR, "Faltan variables de entorno DB_HOST, DB_NAME y DB_USER (y DB_PASS).\n");
    exit(1);
}

$pdo = new PDO(
    "mysql:host=$host;dbname=$name;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$force = in_array('--force', $argv, true);
$existentes = (int)$pdo->query('SELECT COUNT(*) FROM torneos')->fetchColumn();
if ($existentes > 0 && !$force) {
    fwrite(STDERR, "La base ya tiene $existentes torneos. Use --force para agregar mas datos.\n");
    exit(1);
}

mt_srand(2026);                                   // semilla fija: datos reproducibles
$clave = getenv('SEED_CLAVE') ?: 'Prueba2026!';
$hash  = password_hash($clave, PASSWORD_DEFAULT);
$sufijo = $force ? '_' . substr(bin2hex(random_bytes(3)), 0, 6) : '';

$ROL_ADMIN = 1; $ROL_ORGANIZADOR = 2; $ROL_PARTICIPANTE = 3;
$TIPO_LIGA = 1; $TIPO_ELIMINACION = 2; $TIPO_SUIZO = 3;

$pdo->beginTransaction();
try {
    $insUsuario = $pdo->prepare(
        'INSERT INTO usuarios (rol_id, nombre_usuario, email, password_hash) VALUES (?,?,?,?)'
    );
    $nuevoUsuario = function (int $rol, string $nombre) use ($insUsuario, $hash, $pdo, $sufijo): int {
        $nombre .= $sufijo;
        $insUsuario->execute([$rol, $nombre, "$nombre@genesys.test", $hash]);
        return (int)$pdo->lastInsertId();
    };

    // --- Usuarios: 1 administrador, 4 organizadores, 50 participantes (55 en total)
    $adminId = $nuevoUsuario($ROL_ADMIN, 'admin');
    $organizadores = [];
    for ($i = 1; $i <= 4; $i++) {
        $organizadores[] = $nuevoUsuario($ROL_ORGANIZADOR, "organizador$i");
    }
    $usuariosParticipantes = [];
    for ($i = 1; $i <= 50; $i++) {
        $usuariosParticipantes[] = $nuevoUsuario($ROL_PARTICIPANTE, sprintf('participante%02d', $i));
    }

    // --- Equipos (50) y participantes (50)
    $insEquipo = $pdo->prepare('INSERT INTO equipos (nombre) VALUES (?)');
    $insPart   = $pdo->prepare('INSERT INTO participantes (nombre, usuario_id, equipo_id) VALUES (?,?,?)');
    $participantes = [];
    for ($i = 1; $i <= 50; $i++) {
        $insEquipo->execute(["Equipo $i$sufijo"]);
        $equipoId = (int)$pdo->lastInsertId();
        $insPart->execute(["Jugador $i$sufijo", $usuariosParticipantes[$i - 1], $equipoId]);
        $participantes[] = (int)$pdo->lastInsertId();
    }

    // --- Torneos (50), inscripciones (200), rondas (50), enfrentamientos (100), resultados (100)
    $insTorneo = $pdo->prepare(
        'INSERT INTO torneos (organizador_id, tipo_id, nombre, ida_vuelta, rondas_suizo, estado) VALUES (?,?,?,?,?,?)'
    );
    $insInsc   = $pdo->prepare('INSERT INTO inscripciones (torneo_id, participante_id) VALUES (?,?)');
    $insRonda  = $pdo->prepare('INSERT INTO rondas (torneo_id, numero, estado) VALUES (?,1,?)');
    $insEnf    = $pdo->prepare(
        'INSERT INTO enfrentamientos (ronda_id, participante_a_id, participante_b_id, fecha_hora) VALUES (?,?,?,?)'
    );
    $insRes    = $pdo->prepare(
        'INSERT INTO resultados (enfrentamiento_id, puntos_a, puntos_b, registrado_por) VALUES (?,?,?,?)'
    );

    $tipos = [$TIPO_LIGA, $TIPO_ELIMINACION, $TIPO_SUIZO];
    $estados = ['publicado', 'publicado', 'cerrado', 'borrador'];
    $nombresTipo = [1 => 'Liga', 2 => 'Copa', 3 => 'Suizo'];

    for ($t = 1; $t <= 50; $t++) {
        $organizador = $organizadores[($t - 1) % count($organizadores)];
        $pdo->exec('SET @usuario_id = ' . (int)$organizador);   // la auditoria registra quien lo hizo
        $tipo = $tipos[($t - 1) % count($tipos)];
        $estado = $estados[($t - 1) % count($estados)];
        $insTorneo->execute([
            $organizador, $tipo, "{$nombresTipo[$tipo]} de prueba $t$sufijo",
            (int)($tipo === $TIPO_LIGA && $t % 2 === 0),
            $tipo === $TIPO_SUIZO ? 2 : null,
            $estado,
        ]);
        $torneoId = (int)$pdo->lastInsertId();

        // 4 participantes por torneo, rotando para repartir las inscripciones
        $inscriptos = [];
        for ($k = 0; $k < 4; $k++) {
            $pid = $participantes[(($t - 1) * 4 + $k) % count($participantes)];
            $insInsc->execute([$torneoId, $pid]);
            $inscriptos[] = $pid;
        }

        $insRonda->execute([$torneoId, $estado === 'cerrado' ? 'cerrada' : 'abierta']);
        $rondaId = (int)$pdo->lastInsertId();

        // 2 enfrentamientos por ronda: (0 vs 1) y (2 vs 3)
        foreach ([[0, 1], [2, 3]] as $par) {
            $fecha = date('Y-m-d H:i:s', strtotime('2026-11-01 18:00:00') + ($t * 3600));
            $insEnf->execute([$rondaId, $inscriptos[$par[0]], $inscriptos[$par[1]], $fecha]);
            $enfId = (int)$pdo->lastInsertId();

            $a = mt_rand(0, 5);
            $b = mt_rand(0, 5);
            if ($tipo === $TIPO_ELIMINACION && $a === $b) {   // la eliminatoria no admite empates
                $b = $a + 1;
            }
            $insRes->execute([$enfId, $a, $b, $organizador]);
        }
    }

    // Registro explicito de la carga (ademas de lo que anotan los triggers)
    $pdo->prepare(
        'INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle) VALUES (?,?,?,?,?)'
    )->execute([$adminId, 'SEED', 'sistema', null, json_encode(['mensaje' => 'Carga de datos de prueba'])]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'ERROR, se deshizo la carga: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Carga completada. Registros por tabla:\n";
foreach (['usuarios', 'equipos', 'participantes', 'torneos', 'inscripciones', 'rondas',
          'enfrentamientos', 'resultados', 'auditoria'] as $tabla) {
    $n = (int)$pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
    printf("  %-16s %d\n", $tabla, $n);
}
echo "Usuarios de prueba: admin, organizador1..4, participante01..50 (clave: la de SEED_CLAVE o Prueba2026!)\n";
