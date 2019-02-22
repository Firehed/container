<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionHandlerInterface;
use SessionIdInterface;

class SessionHandler implements SessionIdInterface, SessionHandlerInterface
{
    public function __construct(SessionIdInterface $id)
    {
        $this->id = $id;
    }

    public function close()
    {
    }

    public function create_sid()
    {
        return $this->id->create_sid();
    }

    public function destroy(string $session_id)
    {
    }

    public function gc(int $maxlifetime)
    {
    }

    public function open(string $save_path, string $session_name)
    {
    }

    public function read(string $session_id)
    {
    }

    public function write(string $session_id, string $session_data)
    {
    }
}
