<?php

namespace Nerow\Services;

class ServiceManager
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
        return file_exists(static::getFileServicePath($serviceName));
    }

    public static function makeFileService(string $fileName, mixed $content): bool
    {
        return file_put_contents(static::getFileServicePath($fileName), $content);
    }

    public static function getFileServicePath(string $fileName): string
    {
        return static::serviceFolderPath() . '\\' . $fileName . '.php';
    }

    public static function getStubFile(string $stubName): string|false
    {
        return file_get_contents(__DIR__ . '\\..\\stubs\\' . $stubName . '.stub');
    }
}
