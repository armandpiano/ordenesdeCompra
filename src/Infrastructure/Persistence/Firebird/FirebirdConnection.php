<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Persistence\Firebird;

class FirebirdConnection
{
    /**
     * @var resource|null
     */
    private $connection;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function connect(): void
    {
        if ($this->connection !== null) {
            return;
        }

        if (!function_exists('ibase_connect')) {
            throw new \RuntimeException('La extensión de Firebird no está disponible en este entorno.');
        }

        $this->connection = @ibase_connect(
            $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            $this->config['charset']
        );

        if ($this->connection === false) {
            throw new \RuntimeException('No se pudo conectar a Firebird: ' . ibase_errmsg());
        }
    }

    /**
     * @param string $sql
     * @param array $params
     * @return resource
     */
    public function query($sql, array $params = [])
    {
        $this->connect();
        $stmt = @ibase_query($this->connection, $sql, ...$params);
        if ($stmt === false) {
            throw new \RuntimeException('Error al ejecutar consulta: ' . ibase_errmsg());
        }
        return $stmt;
    }

    /**
     * @param resource $result
     * @return array|null
     */
    public function fetchAssoc($result)
    {
        $row = ibase_fetch_assoc($result);
        if ($row === false) {
            return null;
        }
        return $row;
    }
}
