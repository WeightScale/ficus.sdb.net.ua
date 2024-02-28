<?php

namespace accounting;

class Notify {

    static function email($email, $subject, $message): bool {
        return mail($email, "=?utf-8?B?" . base64_encode($subject) . "?=", "<html lang='ru'><head><title>" . $subject . "</title></head><body>" . $message . "</body></html>", "Content-type: text/html; charset=utf-8\r\nFrom: =?utf-8?B?" . base64_encode("sdb.net.ua") . "?= <scales@sdb.net.ua>\r\n");
    }

}