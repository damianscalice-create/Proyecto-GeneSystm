<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responder(401, ['exito' => false, 'mensaje' => 'Debés iniciar sesión.']);
}

require_once __DIR__ . '/../../config/config.php';

function leerJson(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 1048576) {
        responder(413, ['exito' => false, 'mensaje' => 'La solicitud es demasiado grande.']);
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        responder(400, ['exito' => false, 'mensaje' => 'El contenido enviado no es JSON válido.']);
    }
    return $data;
}

function validarTorneo(array $input): array
{
    $types = ['league', 'knockout', 'swiss'];
    $name = trim((string)($input['name'] ?? ''));
    $type = $input['type'] ?? null;
    $players = $input['players'] ?? null;
    $matches = $input['matches'] ?? null;
    $log = $input['log'] ?? [];

    if ($name === '' || mb_strlen($name, 'UTF-8') > 60) {
        responder(400, ['exito' => false, 'mensaje' => 'El nombre debe tener entre 1 y 60 caracteres.']);
    }
    if (!in_array($type, $types, true)) {
        responder(400, ['exito' => false, 'mensaje' => 'El formato del torneo no es válido.']);
    }
    if (!is_array($players) || count($players) < 3 || count($players) > 64) {
        responder(400, ['exito' => false, 'mensaje' => 'El torneo debe tener entre 3 y 64 participantes.']);
    }
    if (!is_array($matches) || count($matches) > 4096 || !is_array($log) || count($log) > 60) {
        responder(400, ['exito' => false, 'mensaje' => 'Los datos del torneo no son válidos.']);
    }

    $cleanPlayers = [];
    foreach ($players as $player) {
        if (!is_string($player)) {
            responder(400, ['exito' => false, 'mensaje' => 'Hay un participante inválido.']);
        }
        $player = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $player) ?? '');
        if ($player === '' || mb_strlen($player, 'UTF-8') > 40 || in_array($player, $cleanPlayers, true)) {
            responder(400, ['exito' => false, 'mensaje' => 'Los participantes deben ser únicos y tener hasta 40 caracteres.']);
        }
        $cleanPlayers[] = $player;
    }

    $playerSet = array_fill_keys($cleanPlayers, true);
    $cleanMatches = [];
    foreach ($matches as $match) {
        if (!is_array($match)
            || !isset($match['id'], $match['r'], $match['a'], $match['done'])
            || !is_string($match['id'])
            || !preg_match('/^[a-zA-Z0-9_-]{1,32}$/', $match['id'])
            || !is_int($match['r'])
            || $match['r'] < 1 || $match['r'] > 4096
            || !is_string($match['a'])
            || !isset($playerSet[$match['a']])
            || !is_bool($match['done'])) {
            responder(400, ['exito' => false, 'mensaje' => 'Hay un enfrentamiento inválido.']);
        }

        $opponent = $match['b'] ?? null;
        if ($opponent !== null && (!is_string($opponent) || !isset($playerSet[$opponent]) || $opponent === $match['a'])) {
            responder(400, ['exito' => false, 'mensaje' => 'Hay un participante inválido en un enfrentamiento.']);
        }

        $scoreA = $match['sa'] ?? null;
        $scoreB = $match['sb'] ?? null;
        if ($opponent === null) {
            if (!$match['done'] || $scoreA !== null || $scoreB !== null) {
                responder(400, ['exito' => false, 'mensaje' => 'El pase libre tiene datos de resultado inválidos.']);
            }
        } elseif ($match['done']) {
            if (!is_int($scoreA) || !is_int($scoreB) || $scoreA < 0 || $scoreA > 999 || $scoreB < 0 || $scoreB > 999
                || ($type === 'knockout' && $scoreA === $scoreB)) {
                responder(400, ['exito' => false, 'mensaje' => 'Hay un resultado inválido.']);
            }
        } elseif (($scoreA !== null && (!is_int($scoreA) || $scoreA < 0 || $scoreA > 999))
            || ($scoreB !== null && (!is_int($scoreB) || $scoreB < 0 || $scoreB > 999))) {
            responder(400, ['exito' => false, 'mensaje' => 'Hay un resultado inválido.']);
        }

        $date = $match['dt'] ?? null;
        if ($date !== null && (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $date))) {
            responder(400, ['exito' => false, 'mensaje' => 'Hay una fecha de enfrentamiento inválida.']);
        }

        $cleanMatches[] = [
            'id' => $match['id'],
            'r' => $match['r'],
            'a' => $match['a'],
            'b' => $opponent,
            'sa' => $scoreA,
            'sb' => $scoreB,
            'done' => $match['done'],
            'dt' => $date,
        ];
    }

    $cleanLog = [];
    foreach ($log as $entry) {
        if (!is_array($entry) || !isset($entry['ts'], $entry['text']) || !is_numeric($entry['ts']) || !is_string($entry['text'])) {
            responder(400, ['exito' => false, 'mensaje' => 'El historial del torneo no es válido.']);
        }
        $cleanLog[] = [
            'ts' => (int)$entry['ts'],
            'text' => mb_substr($entry['text'], 0, 300, 'UTF-8'),
        ];
    }

    $tournament = [
        'name' => $name,
        'type' => $type,
        'players' => $cleanPlayers,
        'dbl' => $type === 'league' && !empty($input['dbl']),
        'matches' => $cleanMatches,
        'log' => $cleanLog,
    ];

    if ($type === 'swiss') {
        $rounds = $input['rounds'] ?? null;
        if (!is_int($rounds) || $rounds < 1 || $rounds > 16) {
            responder(400, ['exito' => false, 'mensaje' => 'La cantidad de rondas no es válida.']);
        }
        $tournament['rounds'] = $rounds;
    }

    if (isset($input['id']) && is_string($input['id'])) {
        $tournament['id'] = $input['id'];
    }
    return $tournament;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$userId = (int)$_SESSION['usuario_id'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT id, datos FROM gestor_torneos WHERE usuario_id = :usuario_id ORDER BY fecha_actualizacion DESC');
        $stmt->execute([':usuario_id' => $userId]);
        $tournaments = [];
        foreach ($stmt->fetchAll() as $row) {
            $tournament = json_decode($row['datos'], true);
            if (!is_array($tournament)) {
                throw new RuntimeException('Stored tournament data is invalid.');
            }
            $tournament['id'] = $row['id'];
            $tournaments[] = $tournament;
        }
        responder(200, [
            'exito' => true,
            'torneos' => $tournaments,
            'usuario' => $_SESSION['nombre_usuario'] ?? '',
        ]);
    }

    if ($method === 'POST') {
        $tournament = validarTorneo(leerJson());
        $id = bin2hex(random_bytes(16));
        $tournament['id'] = $id;
        $stmt = $pdo->prepare('INSERT INTO gestor_torneos (id, usuario_id, datos) VALUES (:id, :usuario_id, :datos)');
        $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $userId,
            ':datos' => json_encode($tournament, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
        responder(201, ['exito' => true, 'torneo' => $tournament]);
    }

    if ($method === 'PUT') {
        $input = leerJson();
        $id = $input['id'] ?? '';
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/', $id)) {
            responder(400, ['exito' => false, 'mensaje' => 'ID de torneo inválido.']);
        }
        $tournament = validarTorneo($input);
        $tournament['id'] = $id;
        $stmt = $pdo->prepare('UPDATE gestor_torneos SET datos = :datos WHERE id = :id AND usuario_id = :usuario_id');
        $stmt->execute([
            ':datos' => json_encode($tournament, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ':id' => $id,
            ':usuario_id' => $userId,
        ]);
        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT 1 FROM gestor_torneos WHERE id = :id AND usuario_id = :usuario_id');
            $exists->execute([':id' => $id, ':usuario_id' => $userId]);
            if (!$exists->fetchColumn()) {
                responder(404, ['exito' => false, 'mensaje' => 'No se encontró el torneo.']);
            }
        }
        responder(200, ['exito' => true, 'torneo' => $tournament]);
    }

    if ($method === 'DELETE') {
        $input = leerJson();
        $id = $input['id'] ?? '';
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/', $id)) {
            responder(400, ['exito' => false, 'mensaje' => 'ID de torneo inválido.']);
        }
        $stmt = $pdo->prepare('DELETE FROM gestor_torneos WHERE id = :id AND usuario_id = :usuario_id');
        $stmt->execute([':id' => $id, ':usuario_id' => $userId]);
        if ($stmt->rowCount() === 0) {
            responder(404, ['exito' => false, 'mensaje' => 'No se encontró el torneo.']);
        }
        responder(200, ['exito' => true]);
    }

    header('Allow: GET, POST, PUT, DELETE');
    responder(405, ['exito' => false, 'mensaje' => 'Método no permitido.']);
} catch (Throwable $error) {
    error_log('gestorTorneos.php: ' . $error->getMessage());
    responder(500, ['exito' => false, 'mensaje' => 'No se pudo procesar el torneo.']);
}
