<?php
require_once __DIR__ . '/exigirLogin.php';
$uid = exigirLogin();

try {
    $stmt = $pdo->prepare(
        "SELECT t.id, t.nombre_evento, t.deporte, t.detalle,
                t.cantidad_participantes AS cupo, t.fecha_creacion,
                u.nombre_usuario AS creador,
                (SELECT COUNT(*) FROM inscripciones i WHERE i.torneo_id = t.id) AS inscriptos,
                EXISTS(SELECT 1 FROM inscripciones i
                       WHERE i.torneo_id = t.id AND i.usuario_id = :uid) AS inscrito
         FROM torneos t
         LEFT JOIN usuarios u ON t.usuario_id = u.id
         ORDER BY t.fecha_creacion DESC"
    );
    $stmt->execute([':uid' => $uid]);
    $torneos = $stmt->fetchAll();

    foreach ($torneos as &$t) {
        $t['cupo'] = (int)$t['cupo'];
        $t['inscriptos'] = (int)$t['inscriptos'];
        $t['inscrito'] = (bool)$t['inscrito'];
        $t['lleno'] = $t['inscriptos'] >= $t['cupo'];
    }
    unset($t);

    echo json_encode(["exito" => true, "torneos" => $torneos]);
} catch (PDOException $e) {
    error_log("torneosDisponibles: " . $e->getMessage());
    responder(500, false, "No se pudieron cargar los torneos.");
}
