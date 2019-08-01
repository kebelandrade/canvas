<?php

require_once 'vendor/autoload.php';

class Canvas implements \Ratchet\MessageComponentInterface
{

    private $conexiones = [];
    private $conexion;
    private $extraccion = [];


    public function __construct(PDO $pdo)
    {
        $this->conexion = $pdo;
    }

    function onOpen(\Ratchet\ConnectionInterface $conn)
    {

        $consulta = $this->conexion->query("SELECT * FROM canvas.coordenadas");
        foreach ($consulta as $con) {
          $this->extraccion[] = $con['coord_x'];
          $this->extraccion[] = $con['coord_y'];
          $this->extraccion[] = $con['color'];
          $this->extraccion[] = $con['grosor'];
        }

        $funcional = json_decode($this->extraccion, true);

        foreach ($this->conexiones as $conexione) {
            $conexione->send($this->extraccion);
        }

        $this->conexiones[] = $conn;
    }

    function onClose(\Ratchet\ConnectionInterface $conn)
    {
        // TODO: Implement onClose() method.
    }

    function onError(\Ratchet\ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    function onMessage(\Ratchet\ConnectionInterface $from, $msg)
    {
        $datos = json_decode($msg, true);
        $sentencia = $this->conexion->prepare("INSERT INTO coordenadas (coord_x, coord_y, color, grosor)".
                    " VALUES (?, ?, ?, ?)");
        foreach ($datos as $coordenada) {
            $sentencia->bindValue(1, $coordenada['x']);
            $sentencia->bindValue(2, $coordenada['y']);
            $sentencia->bindValue(3, $coordenada['color']);
            $sentencia->bindValue(4, $coordenada['grosor']);
            $sentencia->execute();
        }

        foreach ($this->conexiones as $conex) {
            if ($conex != $from) {
                $conex->send($msg);
            }
        }
    }
}

