<?php
declare(strict_types=1);

/**
 * Modelo Usuario: acceso a datos de la tabla `usuarios` (capa Modelo de MVC).
 * No contiene HTML ni lee la peticion HTTP: eso le toca al controlador.
 */
final class Usuario
{
    public const MAX_INTENTOS = 5;
    public const MINUTOS_BLOQUEO = 15;

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function buscarPorNombre(string $nombre): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM usuarios WHERE nombre_usuario = :n');
        $st->execute([':n' => $nombre]);
        return $st->fetch() ?: null;
    }

    public function existe(string $nombre, string $email): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE nombre_usuario = :n OR email = :e');
        $st->execute([':n' => $nombre, ':e' => $email]);
        return (bool)$st->fetchColumn();
    }

    /** Crea un usuario con la clave hasheada y devuelve su id. */
    public function crear(string $nombre, string $email, string $clave, int $rolId): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO usuarios (rol_id, nombre_usuario, email, password_hash) VALUES (:r, :n, :e, :p)'
        );
        $st->execute([
            ':r' => $rolId,
            ':n' => $nombre,
            ':e' => $email,
            ':p' => password_hash($clave, PASSWORD_DEFAULT),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function estaBloqueado(array $usuario): bool
    {
        return $usuario['bloqueado_hasta'] !== null
            && strtotime((string)$usuario['bloqueado_hasta']) > time();
    }

    /**
     * Verifica la clave y lleva la cuenta de intentos fallidos.
     * Devuelve true si la clave es correcta.
     */
    public function verificarClave(array $usuario, string $clave): bool
    {
        if (password_verify($clave, $usuario['password_hash'])) {
            $this->pdo->prepare(
                'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id'
            )->execute([':id' => $usuario['id']]);

            // Actualiza el hash si el algoritmo por defecto de PHP cambio
            if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
                $this->pdo->prepare('UPDATE usuarios SET password_hash = :h WHERE id = :id')
                    ->execute([':h' => password_hash($clave, PASSWORD_DEFAULT), ':id' => $usuario['id']]);
            }
            return true;
        }

        $intentos = (int)$usuario['intentos_fallidos'] + 1;
        $bloqueo = null;
        if ($intentos >= self::MAX_INTENTOS) {
            $bloqueo = date('Y-m-d H:i:s', strtotime('+' . self::MINUTOS_BLOQUEO . ' minutes'));
        }
        $this->pdo->prepare(
            'UPDATE usuarios SET intentos_fallidos = :i, bloqueado_hasta = :b WHERE id = :id'
        )->execute([':i' => $intentos, ':b' => $bloqueo, ':id' => $usuario['id']]);
        return false;
    }
}

?>