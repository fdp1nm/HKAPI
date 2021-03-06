<?php

namespace HKAPI;


use HKAPI\Exceptions\HKAPIInvalidZoneException;
use HKAPI\Exceptions\HKAPISocketException;
use HKAPI\Exceptions\HKAPITimeoutException;

class API
{
    /**
     * Relative path to template folder.
     */
    const TEMPLATE_PATH = '/../templates/';

    /**
     * Timeout in seconds.
     */
    const RESPONSE_TIMEOUT = 2;

    /**
     * Default zones (for my AVR 370).
     */
    const DEFAULT_ZONES = ['Main Zone', 'Zone 2'];

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var Zone[]
     */
    protected $zones = [];

    /**
     * @var resource
     */
    protected $socket;

    /**
     * API constructor.
     *
     * @param string $ip
     * @param int $port
     * @param array $zones List of available zones
     */
    public function __construct($ip, $port = 10025, $zones = [])
    {
        $this->ip = $ip;
        $this->port = (int)$port;

        if (empty($zones)) {
            $zones = self::DEFAULT_ZONES;
        }
        foreach ($zones as $zone) {
            $this->zones[$zone] = new Zone($this, $zone);
        }

        $this->connect();
    }

    /**
     * Get Zone object.
     *
     * @param string $name
     * @return Zone
     * @throws HKAPIInvalidZoneException
     */
    public function zone($name)
    {
        if (isset($this->zones[$name])) {
            return $this->zones[$name];
        }
        throw new HKAPIInvalidZoneException('Zone could not be found: ' . $name);
    }

    /**
     * Verify socket connection. On missing or ended socket, connect.
     *
     * @throws HKAPISocketException
     */
    protected function connect()
    {
        if (is_resource($this->socket)) {
            if (!feof($this->socket)) {
                return;
            } else {
                socket_close($this->socket);
            }
        }
        $errno = null;
        $errmsg = null;
        $this->socket = @fsockopen($this->ip, $this->port, $errno, $errmsg, self::RESPONSE_TIMEOUT);

        if (!is_resource($this->socket)) {
            throw new HKAPISocketException($errmsg, $errno);
        }

        stream_set_blocking($this->socket, 0);
    }

    /**
     * Manually send raw XML request to AVR.
     * You should not use this method since there are Actions for all types.
     *
     * @param string $data
     * @throws HKAPISocketException
     */
    public function sendRequest($data)
    {
        // Verify connection before every request
        $this->connect();

        // Clear buffer
        fread($this->socket, 4096);

        fwrite($this->socket, sprintf(
            "\r\nPOST AVR HTTP/1.1\r\nHost: 10.21.219.218:10025\r\nUser-Agent: Harman Kardon AVR Remote Controller /2.0\r\nContent-Length: %d\r\n%s",
            strlen($data),
            $data
        ));
    }

    /**
     * Read response from AVR or throw exception after timeout exceeded.
     *
     * @return string XML response
     * @throws HKAPITimeoutException
     */
    public function readResponse()
    {
        $i = 0;
        do {
            if ($i >= self::RESPONSE_TIMEOUT * 2) {
                throw new HKAPITimeoutException(sprintf(
                    'Exceeded timeout of %d seconds while waiting for response.',
                    self::RESPONSE_TIMEOUT
                ));
            }
            $response = fread($this->socket, 4096);
            usleep(500000); // wait half a second
            $i++;
        } while (empty($response));

        return '<?xml' . explode('<?xml', $response)[1] . '>';
    }

    /**
     * Generate request using XML template.
     *
     * @param string $name Action name.
     * @param string $zone Zone name.
     * @param string|null $para Parameter, if any.
     * @param string $template Name of template file.
     * @return string
     */
    public function generateRequest($name, $zone, $para = null, $template = 'hk')
    {
        return trim(str_replace(
            ['{{ name }}', '{{ zone }}', '{{ para }}'],
            [$name, $zone, $para],
            file_get_contents(__DIR__ . self::TEMPLATE_PATH . $template . '.xml')
        ));
    }
}