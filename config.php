<?php

// Тут нужно заменить домен на желаемый
define('TARGET_HOST', "lordfilm.it");

// Адрес на который будут изменяться ссылки
define('REPLACE_HOST', "seasonvar.taxibek.ru");

define('TARGET_SCHEME', "https");

// Если на основном домене стоит basic авторизация (Если нет оставить пустым)
//define('TARGET_AUTH', "admin:admin");

// Логирование получаемых ссылок
define('REQUEST_LOG', false);

// Тайм-аут запроса (Если нужно)
define('REQUEST_TIMEOUT', 180);
