<?php


session_start();
require_once __DIR__ . '/php/config.php';
 
$id = $_GET['id'] ?? null;
 
if (!is_numeric($id)) {
    die("Torneo no válido.");
}
 
$stmt = $pdo->prepare(
    "SELECT t.*, u.nombre_usuario AS creador
     FROM torneos t
     LEFT JOIN usuarios u ON t.usuario_id = u.id
     WHERE t.id = :id"
);
$stmt->execute([':id' => $id]);
$torneo = $stmt->fetch();
 
if (!$torneo) {
    die("El torneo solicitado no existe.");
}
 
// ---------------- Traducir el "detalle" a texto legible ----------------
$detalleLegible = [
    'male' => 'Masculino',
    'fem' => 'Femenino',
];
$detalleMostrado = $torneo['detalle']
    ? ($detalleLegible[$torneo['detalle']] ?? $torneo['detalle'])
    : null;
 
// ---------------- Generar el bracket (cuadro de enfrentamientos) ----------------
function generarBracket(int $cantidad): array {
    $tamano = 2 ** (int)ceil(log(max($cantidad, 2), 2));
 
    $jugadores = [];
    for ($i = 1; $i <= $cantidad; $i++) {
        $jugadores[] = "Jugador $i";
    }
    while (count($jugadores) < $tamano) {
        $jugadores[] = "BYE";
    }
    shuffle($jugadores);
 
    $rondas = [];
    $rondaActual = [];
    for ($i = 0; $i < count($jugadores); $i += 2) {
        $rondaActual[] = [$jugadores[$i], $jugadores[$i + 1]];
    }
    $rondas[] = $rondaActual;
 
    $numRondas = (int)log($tamano, 2);
 
    for ($r = 1; $r < $numRondas; $r++) {
        $rondaAnterior = $rondas[$r - 1];
        $ganadoresPrevios = [];
 
        foreach ($rondaAnterior as $idx => $match) {
            [$a, $b] = $match;
            if ($a === 'BYE' && $b === 'BYE') {
                $ganador = 'BYE';
            } elseif ($a === 'BYE') {
                $ganador = $b;
            } elseif ($b === 'BYE') {
                $ganador = $a;
            } else {
                $ganador = "Ganador Partido " . ($idx + 1) . " (Ronda $r)";
            }
            $ganadoresPrevios[] = $ganador;
        }
 
        $nuevaRonda = [];
        for ($i = 0; $i < count($ganadoresPrevios); $i += 2) {
            $nuevaRonda[] = [$ganadoresPrevios[$i], $ganadoresPrevios[$i + 1] ?? 'BYE'];
        }
        $rondas[] = $nuevaRonda;
    }
 
    return $rondas;
}
 
$rondas = generarBracket((int)$torneo['cantidad_participantes']);
$nombresRondas = [];
$totalRondas = count($rondas);
foreach ($rondas as $i => $ronda) {
    if ($i === $totalRondas - 1) {
        $nombresRondas[] = "Final";
    } elseif ($i === $totalRondas - 2) {
        $nombresRondas[] = "Semifinal";
    } else {
        $nombresRondas[] = "Ronda " . ($i + 1);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/torneos.css">
        <link rel="stylesheet" href="css/bracket.css">
        <link rel="icon" href="assets/bombilla.png" type="image/x-icon">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
        <title>Torneo: <?php echo htmlspecialchars($torneo['nombre_evento']); ?></title>
    </head>
    <body>
 
        <button type="button" id="botonAbrir">
            <img src="assets/bombilla.png" alt="" srcset="">
        </button>
 
        <nav id="miSidebar" class="sidebar" aria-label="Navegación principal">
            <button id="botonCerrar" class="close-btn" aria-label="Cerrar sidebar" title="Cerrar">&times;</button>
            <ul>
                <li><a href="index.html">Inicio</a></li>
                <li><a href="sobreNosotros.html">Sobre nosotros</a></li>
                <li><a href="torneos.html">Crear torneo</a></li>
                <li><a href="ver_torneos.php">Torneos guardados</a></li>
            </ul>
        </nav>
 
        <div class="Borde_lol1 resumen-torneo">
            <h2 class="titulo"><?php echo htmlspecialchars($torneo['nombre_evento']); ?></h2>
 
            <div class="resumen-grid">
                <div class="resumen-item">
                    <span class="resumen-etiqueta">Deporte</span>
                    <span class="resumen-valor"><?php echo htmlspecialchars($torneo['deporte']); ?></span>
                </div>
 
                <?php if ($detalleMostrado): ?>
                <div class="resumen-item">
                    <span class="resumen-etiqueta"><?php echo $torneo['deporte'] === 'E-sports' || $torneo['deporte'] === 'Juego de mesa' ? 'Juego' : 'Categoría'; ?></span>
                    <span class="resumen-valor"><?php echo htmlspecialchars($detalleMostrado); ?></span>
                </div>
                <?php endif; ?>
 
                <div class="resumen-item">
                    <span class="resumen-etiqueta">Participantes</span>
                    <span class="resumen-valor"><?php echo (int)$torneo['cantidad_participantes']; ?></span>
                </div>
 
                <div class="resumen-item">
                    <span class="resumen-etiqueta">Creado por</span>
                    <span class="resumen-valor"><?php echo htmlspecialchars($torneo['creador'] ?? 'Anónimo'); ?></span>
                </div>
 
                <div class="resumen-item">
                    <span class="resumen-etiqueta">Fecha</span>
                    <span class="resumen-valor"><?php echo date('d/m/Y H:i', strtotime($torneo['fecha_creacion'])); ?></span>
                </div>
            </div>
        </div>
 
        <div class="Borde_lol1 bracket-contenedor">
            <h2 class="titulo">Cuadro del torneo</h2>
                
            <div class="bracket">
                <?php foreach ($rondas as $i => $ronda): ?>
                    <div class="bracket-ronda">
                        
                        <h3 class="bracket-ronda-titulo"><?php echo $nombresRondas[$i]; ?></h3>
                        <?php foreach ($ronda as $match): ?>
                        
                        <div class="bracket-partido">
                                
                            <div class="bracket-jugador <?php echo $match[0] === 'BYE' ? 'bye' : ''; ?>">
                                <?php echo htmlspecialchars($match[0]); ?>
                            </div>
                            
                            <div class="bracket-jugador <?php echo $match[1] === 'BYE' ? 'bye' : ''; ?>">
                                <?php echo htmlspecialchars($match[1]); ?>
                            </div>
            
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
 
            <p class="bracket-nota">
                * Este cuadro es un sorteo inicial con nombres genéricos ("Jugador 1", "Jugador 2"...).
                "BYE" significa que ese lugar quedó libre y el jugador avanza directo a la siguiente ronda.
            </p>
        </div>
 
    </body>
</html>

?>