<?php
// ============================================================
// GestActivos - Clase de conexión PDO (con soporte de transacciones)
// ============================================================

require_once BASE_PATH . '/config/database.php';

class Database {

    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE,        PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // No exponer detalles de la conexión (host, usuario, etc.) al cliente.
            error_log('GestActivos - Error de conexión a BD: ' . $e->getMessage());
            http_response_code(500);
            die('No se pudo conectar a la base de datos. Contacta al administrador.');
        }
    }

    // Singleton: una sola conexión por request
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // SELECT → devuelve array de filas
    public function consulta(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // INSERT / UPDATE / DELETE → devuelve lastInsertId
    public function ejecutar(string $sql, array $params = []): string {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    // SELECT fila única → devuelve array o null
    public function fila(string $sql, array $params = []): ?array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // SELECT COUNT(*) tipo de consulta → devuelve el entero directamente
    public function contar(string $sql, array $params = []): int {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ------------------------------------------------------------------
    // Transacciones — agrupa varias escrituras en una sola unidad atómica.
    // Si $fn lanza cualquier excepción, se revierte todo automáticamente.
    //
    // Uso:
    //   $db->transaccion(function (Database $db) {
    //       $id = $db->ejecutar("INSERT INTO usuarios (...) VALUES (...)", [...]);
    //       $db->ejecutar("INSERT INTO permisos (...) VALUES (...)", [...]);
    //       return $id;
    //   });
    // ------------------------------------------------------------------
    public function transaccion(callable $fn) {
        $yaHabiaTransaccion = $this->pdo->inTransaction();
        if (!$yaHabiaTransaccion) {
            $this->pdo->beginTransaction();
        }
        try {
            $resultado = $fn($this);
            if (!$yaHabiaTransaccion) {
                $this->pdo->commit();
            }
            return $resultado;
        } catch (\Throwable $e) {
            if (!$yaHabiaTransaccion && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
