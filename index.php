<?php
require_once './config.php';
require_once './libs/http_build_url.php';

class Proxy {

    private $session, $response;

    private function starts_with($string, $query)
    {
        return substr($string, 0, strlen($query)) === $query;
    }

    private function set_new_url($url)
    {
        // Преобразуем адрес запроса
        // https://www.mysite.com/api/get_products?filter=1 => https://www.example.com/api/get_products?filter=1
        $parsed_url = parse_url($url);

        if (defined('TARGET_HOST')) {
            $parsed_url['host'] = constant('TARGET_HOST');
        }

        if (defined('TARGET_SCHEME')) {
            $parsed_url['scheme'] = constant('TARGET_SCHEME');
        }

        return http_build_url($parsed_url);
    }

    private function get_cookies()
    {
        $cookie_string = '';
        foreach ($_COOKIE as $key => $value) {
            $cookie_string .= "$key=$value;";
        };
        return $cookie_string;
    }

    public function push_to_log($str)
    {
        file_put_contents('./log.txt', $str."\n", FILE_APPEND | LOCK_EX);
    }

    public function response()
    {
        // Инициализация curl.
        $this->session = curl_init($this->set_new_url($_SERVER['REQUEST_URI']));

        curl_setopt($this->session, CURLOPT_CONNECTTIMEOUT, constant('REQUEST_TIMEOUT'));
        curl_setopt($this->session, CURLOPT_TIMEOUT, constant('REQUEST_TIMEOUT'));

        // Эта реализация поддерживает только POST и GET, можно добавить другие.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            curl_setopt($this->session, CURLOPT_POST, true);
            curl_setopt($this->session, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
        } else {
            curl_setopt($this->session, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
        }

        if (isset($_SERVER["CONTENT_TYPE"])) {
            curl_setopt($this->session, CURLOPT_HTTPHEADER, ["Content-Type: ". $_SERVER['CONTENT_TYPE']]);
        }

        if (defined('TARGET_AUTH')) {
            curl_setopt($this->session, CURLOPT_USERPWD, constant('TARGET_AUTH'));
        }

        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($this->session, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($this->session, CURLOPT_HEADER, true);

        // Передача печеняк в запрос
        curl_setopt($this->session, CURLOPT_COOKIE, $this->get_cookies());

        // Выполняем запрос
        $this->response = curl_exec($this->session);

        // Пара-па-па-па
        $response_body = substr($this->response, curl_getinfo($this->session, CURLINFO_HEADER_SIZE));
        $response_error = curl_error($this->session);
        $response_http_code = curl_getinfo($this->session, CURLINFO_HTTP_CODE);
        $response_content_type = curl_getinfo($this->session, CURLINFO_CONTENT_TYPE);

        curl_close($this->session);

        // Копирование печеняк из запроса в ответ
        $header_text = substr($this->response, 0, strpos($this->response, "\r\n\r\n"));
        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($this->starts_with($line, "Set-Cookie")) {
                header($line, 0);
            }
        }

        header("Content-type: $response_content_type", 1);

        http_response_code($response_http_code);

        // Send the response output
        return $response_error ? $response_error : $response_body;
    }

}

$ech = new Proxy();

if (defined('REQUEST_LOG') && constant('REQUEST_LOG') == true) {
    $ech->push_to_log('Запрашиваемый адрес: '. $_SERVER['REQUEST_URI']);
}

echo $ech->response();
