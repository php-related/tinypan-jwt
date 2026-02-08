<?php

namespace tinypan\jwt;

class Install
{
    // 定义插件标识，方便 Webman 识别
    public const WEBMAN_PLUGIN = true;

    // 配置文件映射，key 是包内路径，value 是目标项目相对路径
    protected static array $pathRelation = [
        './config/jwt.php' => 'config/jwt.php',
    ];

    /**
     * 安装方法 - 拷贝配置文件
     */
    public static function install(): void
    {
        foreach (self::$pathRelation as $source => $dest) {
            $sourcePath = __DIR__ . '/../../' . $source;
            $destPath = self::getBasePath() . '/' . $dest;

            // 确保目录存在
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // 拷贝文件，覆盖目标文件
            copy($sourcePath, $destPath);
        }
    }

    /**
     * 卸载方法 - 删除拷贝的配置文件
     */
    public static function uninstall(): void
    {
        foreach (self::$pathRelation as $source => $dest) {
            $destPath = self::getBasePath() . '/' . $dest;
            if (is_file($destPath)) {
                unlink($destPath);
            }
        }
    }

    /**
     * 获取项目根目录
     * Webman 和 ThinkPHP 都有 base_path() 函数，你可以根据环境调整
     */
    protected static function getBasePath(): string
    {
        // 优先使用全局函数 base_path()，否则用当前工作目录
        if (function_exists('base_path')) {
            return base_path();
        }
        // ThinkPHP 常用的根目录获取，可以改写为你的项目实际路径
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }
        // 兜底：当前工作目录
        return getcwd();
    }
}
