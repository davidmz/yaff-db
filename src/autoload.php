<?php
/**
 * Алгоритм автозагрузки:
 *
 * Структура пространств имён соответствует структуре каталогов.
 * Базовое пр-во имён 'Yaff' соответствует каталогу, в котором расположен автозагрузчик.
 *
 * Имена классов разбиваются на части по правилам CamelCase.
 * Файл, содержащий класс 'MyOwnClass', ищется в следующем порядке: MyOwnClass.php, MyOwn.php, My.php
 * Поиск заканчивается на первом найденном файле.
 *
 */
spl_autoload_register(function ($class) {
    $RootNs = "Yaff";
    $DS     = DIRECTORY_SEPARATOR;

    $parts     = explode("\\", $class);
    $className = array_pop($parts);
    $project   = array_shift($parts);

    if ($project != $RootNs) return;

    $dirName  = __DIR__ . $DS . join($DS, $parts);
    $fileName = $dirName . $DS . $className . ".php";

    if (is_file($fileName)) {
        /** @noinspection PhpIncludeInspection */
        require($fileName);
    } else {
        $parts = preg_split('/ (?<=[a-z])(?=[A-Z]) | (?<=[0-9])(?=[A-Z]) | (?<=[A-Z])(?=[A-Z][a-z]) /x', $className);
        array_pop($parts);
        while (!empty($parts)) {
            $fileName = $dirName . $DS . join($DS, $parts) . '.php';
            if (is_file($fileName)) {
                /** @noinspection PhpIncludeInspection */
                require_once($fileName);
                break;
            }
            array_pop($parts);
        }
    }
});