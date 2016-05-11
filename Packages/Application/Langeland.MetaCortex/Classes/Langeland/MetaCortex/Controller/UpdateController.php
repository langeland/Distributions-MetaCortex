<?php
namespace Langeland\MetaCortex\Controller;

/*
 * This file is part of the Langeland.MetaCortex package.
 */

use TYPO3\Flow\Annotations as Flow;

class UpdateController extends \TYPO3\Flow\Mvc\Controller\ActionController
{
    /**
     * @return void
     */
    public function indexAction()
    {
        $this->response->setHeader('Content-type', 'text/plain; charset=utf8', true);
        $headers = $this->request->getHttpRequest()->getHeaders();

        if ($headers->get('Http-User-Agent') !== 'ESP8266-http-Update') {
            $this->response->setStatus(403);

            return 'Only for ESP8266 updater!';
        }

        if (
            !$headers->get('Http-Esp8266-Sta-Mac') ||
            !$headers->get('Http-Esp8266-Ap-Mac') ||
            !$headers->get('Http-Esp8266-Free-Space') ||
            !$headers->get('Http-Esp8266-Sketch-Size') ||
            !$headers->get('Http-Esp8266-Chip-Size') ||
            !$headers->get('Http-Esp8266-Sdk-Version') ||
            !$headers->get('Http-Esp8266-Version')
        ) {
            $this->response->setStatus(403);

            return 'Only for ESP8266 updater! (header)';
        }

        $db = array(
            "18:FE:AA:AA:AA:AA" => "DOOR-7-g14f53a19",
            "18:FE:AA:AA:AA:BB" => "TEMP-1.0.0"
        );

        if (array_key_exists($headers->get('Http-Esp8266-Sta-Mac'), $db)) {
            if ($db[$headers->get('Http-Esp8266-Sta-Mac')] != $headers->get('Http-Esp8266-Version')) {
                die('Send file');
                //sendFile("./bin/" . $db[$_SERVER['HTTP_X_ESP8266_STA_MAC']] . "bin");
            } else {
                $this->response->setStatus(304);
            }
        } else {
            $this->response->setStatus(404, 'No version for ESP MAC');
        }

        return '';
    }

    protected function sendFile($path)
    {
        header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK', true, 200);
        header('Content-Type: application/octet-stream', true);
        header('Content-Disposition: attachment; filename=' . basename($path));
        header('Content-Length: ' . filesize($path), true);
        header('x-MD5: ' . md5_file($path), true);
        readfile($path);
    }
}
