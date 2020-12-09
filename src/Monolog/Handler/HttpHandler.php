<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Logger;
use Monolog\Formatter\HttpFormatter;

/**
 * Sends log to Http.
 *
 * @author Laurent Glesner <l.glesner@gmail.com>
 */
class HttpHandler extends AbstractProcessingHandler
{
    const ENDPOINT_SINGLE = 'inputs';
    const ENDPOINT_BATCH = 'bulk';

    protected $url;
    protected $token;

    protected $tag = array();

    public function __construct($url, $token, $level = Logger::DEBUG, $bubble = true)
    {
        if (!extension_loaded('curl')) {
            throw new \LogicException('The curl extension is needed to use the LogglyHandler');
        }
        $this->url = $url;
        $this->token = $token;

        parent::__construct($level, $bubble);
    }

    protected function write(array $record)
    {
        $this->send($record["formatted"]);
    }

    public function handleBatch(array $records)
    {
        $level = $this->level;

        $records = array_filter($records, function ($record) use ($level) {
            return ($record['level'] >= $level);
        });

        if ($records) {
            $this->send($this->getFormatter()->formatBatch($records));
        }
    }

    protected function send($data)
    {
        $headers = array('Content-Type: application/json');

        if (!empty($this->token)) {
            $headers[] = 'Authorization : Bearer '.$this->token;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        Curl\Util::execute($ch);
    }

    protected function getDefaultFormatter()
    {
        return new HttpFormatter();
    }
}
