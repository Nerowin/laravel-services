<?php

namespace Nerow\Services\Helpers;

class ServiceHelper
{
    public static function makeServiceFolder(): bool
    {
        $folerPath = static::serviceFolderPath();

        return is_dir($folerPath)
            || mkdir($folerPath, 0777);
    }

    public static function serviceFolderExist(): bool
    {
        return is_dir(static::serviceFolderPath());
    }

    public static function serviceFolderPath(): string
    {
        return app_path('Services');
    }

    public static function serviceFileExist(string $serviceName): bool
    {
        return file_exists(static::getServiceFilePath($serviceName));
    }

    public static function makeServiceFile(string $fileName, mixed $content): bool
    {
        return file_put_contents(static::getServiceFilePath($fileName), $content);
    }

    public static function getServiceFilePath(string $fileName): string
    {
        return static::serviceFolderPath() . '\\' . $fileName . '.php';
    }

    public static function getStubFile(string $stubName): string|false
    {
        return file_get_contents(__DIR__ . '\\..\\stubs\\' . $stubName . '.stub');
    }
}
