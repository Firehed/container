<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionHandlerInterface;
use SessionIdInterface;

class SessionHandler implements SessionIdInterface, SessionHandlerInterface
{
    private $id;

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

    public function destroy($session_id)
    {
    }

    public function gc($maxlifetime)
    {
    }

    public function open($save_path, $session_name)
    {
    }

    public function read($session_id)
    {
    }

    public function write($session_id, $session_data)
    {
    }
}
