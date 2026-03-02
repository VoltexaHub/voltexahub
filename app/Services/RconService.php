<?php

namespace App\Services;

use RuntimeException;

class RconService
{
    private $socket = null;
    private int $requestId = 0;

    private const SERVERDATA_AUTH = 3;
    private const SERVERDATA_AUTH_RESPONSE = 2;
    private const SERVERDATA_EXECCOMMAND = 2;
    private const SERVERDATA_RESPONSE_VALUE = 0;

    public function __construct(
        private string $host,
        private int $port,
        private string $password,
    ) {
    }

    public function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);

        if (! $this->socket) {
            throw new RuntimeException("RCON connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, 5);

        // Authenticate
        $response = $this->sendPacket(self::SERVERDATA_AUTH, $this->password);

        if ($response === false || $response['id'] === -1) {
            $this->disconnect();
            throw new RuntimeException('RCON authentication failed');
        }

        return true;
    }

    public function sendCommand(string $command): string
    {
        if (! $this->socket) {
            throw new RuntimeException('Not connected to RCON server');
        }

        $response = $this->sendPacket(self::SERVERDATA_EXECCOMMAND, $command);

        return $response['body'] ?? '';
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function sendPacket(int $type, string $body): array|false
    {
        $this->requestId++;
        $packet = pack('V', $this->requestId)
            . pack('V', $type)
            . $body . "\x00\x00";

        $packet = pack('V', strlen($packet)) . $packet;

        fwrite($this->socket, $packet);

        return $this->readPacket();
    }

    private function readPacket(): array|false
    {
        $sizeData = fread($this->socket, 4);
        if (strlen($sizeData) < 4) {
            return false;
        }

        $size = unpack('V', $sizeData)[1];
        if ($size < 10 || $size > 4096) {
            return false;
        }

        $data = '';
        $remaining = $size;
        while ($remaining > 0) {
            $chunk = fread($this->socket, $remaining);
            if ($chunk === false || $chunk === '') {
                return false;
            }
            $data .= $chunk;
            $remaining -= strlen($chunk);
        }

        $id = unpack('V', substr($data, 0, 4))[1];
        // Convert unsigned to signed for auth failure detection
        if ($id === 0xFFFFFFFF) {
            $id = -1;
        }
        $type = unpack('V', substr($data, 4, 4))[1];
        $body = substr($data, 8, -2); // Strip null terminators

        return [
            'id' => $id,
            'type' => $type,
            'body' => $body,
        ];
    }
}
