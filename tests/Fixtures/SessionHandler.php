<?php
declare(strict_types=1);

namespace Firehed\Container\Fixtures;

use SessionHandlerInterface;
use SessionIdInterface;

class SessionHandler implements SessionIdInterface, SessionHandlerInterface
{
    /** @var SessionIdInterface */
    private $id;

    public function __construct(SessionIdInterface $id)
    {
        $this->id = $id;
    }

    public function close(): bool
    {
        return true;
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function create_sid(): string
    {
        return $this->id->create_sid();
    }
    // phpcs:enable

    /**
     * @param string $session_id
     */
    public function destroy($session_id): bool
    {
        return true;
    }

    /**
     * @param int $maxlifetime
     * @return int|false
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        return 0;
    }

    /**
     * @param string $save_path
     * @param string $session_name
     */
    public function open($save_path, $session_name): bool
    {
        return true;
    }

    /**
     * @param string $session_id
     */
    public function read($session_id): string
    {
        return '';
    }

    /**
     * @param string $session_id
     * @param string $session_data
     */
    public function write($session_id, $session_data): bool
    {
        return true;
    }
}
