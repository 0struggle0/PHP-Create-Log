<?php

/*
 * @Author: Rebort
 * @Date: 2021-08-22 10:39:46
 * @Email: 940954530@qq.com
 * @LastEditTime: 2021-08-22 10:43:43
 * @Description: 按天记录日志，不区分日志级别
 */

class Log
{
    const BASE_PATH = __DIR__;

    /**
     * 允许打印日志的错误级别
     */
    const LOG_OUTPUT_LEVEL = [
        self::SQL,
        self::ERR,
        self::WARN,
        self::INFO,
        self::DEBUG,
    ];

    const SQL = 'sql';
    const ERR = 'error';
    const INFO = 'info';
    const DEBUG = 'debug';
    const WARN = 'warn';

    /**
     * 基础的日志文件路径
     */
    private static $basePath;
    private static $year;
    private static $month;
    private static $day;

    public static  function __callStatic($name, $arguments)
    {
        // 初始化日期
        self::initDate();

        // 不区分大小写
        $level = strtolower($name);
        if (in_array($level, self::LOG_OUTPUT_LEVEL) && !empty($arguments) && is_array($arguments)) {
            self::record($arguments[0], $level);
        }
    }

    /**
     * 初始化日期
     */
    private static function initDate()
    {
        self::$year = date('Y', time());
        self::$month = date('m', time());
        self::$day = date('d', time());
    }

    /**
     * 记录日志
     * @param $message
     * @param $level
     */
    public static function record(string $message, string $level = self::ERR)
    {
        $day = self::$year . '/' . self::$month . '/' . self::$day;
        self::$basePath = str_replace("\\", "/", __DIR__);
        $fileName = self::$basePath . '/log/' . $day . '.log';
        $url = '[' . ($_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF']) . '] ';

        try {
            $backtrace = debug_backtrace();
            if (!empty($backtrace) && is_array($backtrace)) {
                $line = $backtrace[0]['line'];
                $url .= "[{$backtrace[0]['class']}@{$backtrace[0]['function']}]";
            } else {
                $line = 0;
            }

            // 生成日志文件
            self::create($fileName, self::$basePath, $day);

            $now = date('Y-m-d H:i:s', time());
            if ($level == self::SQL) {
                $content = "[{$now}] [{$level}] {$message}\r\n";
            } else {
                $content = "[{$now}] [{$level}] {$url} [{$line}] {$message}\r\n";
            }

            file_put_contents($fileName, $content, FILE_APPEND);
        } catch (Exception $e) {
        }
    }

    /**
     * 创建日志文件，如果发现日志文件大小超过上限，则重命名后新建
     * 默认一个日志文件为20M
     * @param $fileName
     * @param $path
     * @param $day
     */
    private static function create($fileName, $path, $day)
    {
        if (!file_exists($fileName)) {
            self::createAndAuth($fileName);
            return ;
        }
        
        $fileSize = abs(filesize($fileName));
        // $size = 20 * 1024 * 1024;
        $size = 10;
        if ($fileSize > $size) {
            $renameResult = self::rename($fileName, $path, $day);
            if ($renameResult) {
                self::createAndAuth($fileName);
            }
        }
    }

    /**
     * 创建文件并赋予权限
     * @param $fileName
     * @param int $auth
     */
    private static function createAndAuth($fileName, $auth = 0666)
    {
        $filePart = explode('/', $fileName);
        array_pop($filePart);
        $fileDir = implode('/', $filePart);
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }
        touch($fileName);
        chmod($fileName, $auth);
    }

    /**
     * 重命名文件，用于日志滚动
     * @param $fileName
     * @param $path
     * @param $day
     * @return bool
     */
    private static function rename($fileName, $path, $day)
    {
        $result = false;
        try {
            $allFile = scandir($path . '/log/' . self::$year . '/' . self::$month);
            if (is_array($allFile) && !empty($allFile)) {
                $fileString = implode('', $allFile);
                $currentDay = self::$day;
                preg_match("/{$currentDay}-/", $fileString, $matches);
                $fileCount = count($matches);
                $fileNumber = $fileCount + 1;
            } else {
                $fileNumber = 1;
            }
            $time = date('His', time());
            $newFileName = $path . '/log/' . $day . '-' . $fileNumber . '-' . $time . '.log';
            $result = @rename($fileName, $newFileName);
        } catch (Exception $ex) {
        }
        return $result;
    }
}

Log::info(json_encode(['第一次测试']));
