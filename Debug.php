<?php

/**
 * Small library of utility methods used for debugging.
 */
final class Debug
{
    /**
     * Write formatted data to stdOut.
     *
     * Output mode for writing debug data to stdOut while formatting it to be HTTP request friendly.
     * This means that the data is html-encoded and placed inside a <pre> block.
     *
     * @var int
     */
    const OUTPUT_MODE_STDOUT_FORMAT = 2;

    /**
     * Write unformatted data to stdOut.
     *
     * @var int
     */
    const OUTPUT_MODE_STDOUT_DO_NOT_FORMAT = 3;

    /**
     * Write unformatted data to a file.
     */
    const OUTPUT_MODE_TO_FILE = 4;

    /**
     * @var ?int
     */
    private static $outputMode = null;

    /**
     * @var ?string
     */
    private static $file = null;

    /**
     * @var ?float
     */
    private static $startTime = null;

    /**
     * @var bool
     */
    private static $startWatch = false;

    /**
     * @var bool
     */
    private static $onErrorCalled = false;

    /**
     * Execute the specified callback in the event of a fatal error.
     *
     * In the event of a fatal error, execute the specified callback.
     * Useful for diagnosing errors in versions of PHP before errors
     * were catchable with try/catch.
     *
     * If no callback is supplied then a default callback is used that neatly
     * formats and dumps the error information, then halts execution.
     *
     * @param  callable $callback
     * @return void
     * @throws LogicException
     */
    public static function onError(callable $callback = null)
    {
        if ($callback === null) {
            $callback = (function ($type, $message, $file, $line) {
                self::dump(implode("\n", [
                    'Type:    ' . $type,
                    'Message: ' . $message,
                    'File:    ' . $file,
                    'Line:    ' . $line
                ]));
            });
        }

        if (self::$onErrorCalled === true) {
            throw new LogicException(sprintf(
                '%s::onError() has already been called.',
                get_class($this)
            ));
        }

        register_shutdown_function(function () use ($callback) {
            $error = error_get_last();
            $callback(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        });

        self::$onErrorCalled = true;
    }

    /**
     * Watch
     *
     * First half of a mechanism for debugging something that does not occur on the first iteration through the code.
     * There is another method named startWatch and until it is called this method is silently skipped over when called.
     * This method takes a callable and once startWatch has been called this method will execute
     * that callable each time it is called.
     *
     * @param  callable $callback
     * @return void
     */
    public static function watch(callable $callback)
    {
        if (self::$startWatch === true) {
            call_user_func($callback);
        }
    }

    /**
     * Start watch.
     *
     * Second half of a mechanism for debugging something that does not occur on the first iteration through the code.
     * There is another method named watch that takes a callable. When watch is called it does nothing until
     * this method has been called. After that the callable defined in the watch method is executed.
     *
     * @return void
     * @throws LogicException
     */
    public static function startWatch()
    {
        if (self::$startWatch === true) {
            throw new LogicException(sprintf(
                '%s::startWatch() already called.',
                get_class($this)
            ));
        }

        self::$startWatch = true;
    }

    /**
     * Record current microsecond, used to test performance.
     *
     * @return void
     * @throws LogicException
     */
    public static function startTimer()
    {
        if (self::$startTime !== null) {
            throw new LogicException(sprintf(
                '%s::startTimer has already been called.',
                get_class($this)
            ));
        }

        self::$startTime = microtime(true);
    }

    /**
     * Display the difference in microseconds between now and when startTimer was called.
     *
     * @return void
     * @throws LogicException
     */
    public static function stopTimer()
    {
        if (self::$startTime === null) {
            throw new LogicException(sprintf(
                '%s::stopTimer cannot be called before %s::startTimer.',
                $classPath = get_class($this),
                $classPath
            ));
        }

        self::dump(sprintf(
            'Time elapsed: %d microseconds.',
            (microtime(true) - self::$startTime) * 1000000
        ));
    }

    /**
     * Dump object methods as an array.
     *
     * @param  string|object $strOrObj Classpath string or object.
     * @return void
     * @throws InvalidArgumentException
     */
    public static function dumpObjectMethods($strOrObj)
    {
        if (!is_string($strOrObj) && !is_object($strOrObj)) {
            throw new InvalidArgumentException(sprintf(
                '%s::dumpObjectMethods() parameter must be a string or an object, got %s.',
                get_class($this),
                self::formatType($strOrObj)
            ));
        }

        self::dump(get_class_methods($strOrObj));
    }

    /**
     * Dump value type and exit.
     *
     * @param  mixed $val
     * @return void
     */
    public static function dumpType($val)
    {
        self::dump(self::formatType($val));
    }

    /**
     * Dump the file containing the specified class.
     *
     * @param  string|object $strOrObj Classpath string or object.
     * @return void
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function dumpClassFilePath($strOrObj)
    {
        switch ($type = self::formatType($strOrObj)) {
            case 'object':
                $strOrObj = get_class($strOrObj);
                // Now that the param is a class path string, fall through.

            case 'string':
                break;

            default:
                throw new InvalidArgumentException(sprintf(
                    '%s::dumpClassFilePath() parameter must be a string or an object, got %s.',
                    get_class($this),
                    $type
                ));
        }

        if (!class_exists($strOrObj)) {
            throw new LogicException(sprintf('Class not found: %s.', $strOrObj));
        }

        if (($filePath = (new ReflectionClass($strOrObj))->getFileName()) === false) {
            self::dump(sprintf(
                'PHP core or extension class: %s.',
                is_string($strOrObj) ? $strOrObj : get_class($obj)
            ));
        }

        self::dumpClassFilePath(sprintf('', $strOrObj, $filePath));
    }

    /**
     * Dump a neatly formatted stack trace of how this point in the code was reached.
     *
     * @return void
     */
    public static function dumpTrace()
    {
        self::dump(implode("\n", array_slice(array_map(function ($trace) {
            return implode("\n", array_filter([
                isset($trace['function']) ? 'Function: ' . $trace['function'] : false,
                isset($trace['file']) ? 'File: ' . $trace['file'] : false,
                isset($trace['line']) ? 'Line: ' . $trace['line'] : false,
                "\n",
            ]));
        }, debug_backtrace()), 1)));
    }

    /**
     * Dump data and exit.
     *
     * Takes a variable number of arguments, formats them all as output data,
     * dumps them via the specified output method, then stops execution.
     *
     * @param  mixed $vals, ...
     * @return void
     */
    public static function dump()
    {
        $outputMode = self::fetchOutputMode();
        $vals       = func_get_args();

        if ($outputMode === self::OUTPUT_MODE_STDOUT_FORMAT) {
            $str = self::prepareFormatted($vals);
        } else {
            $str = self::prepareNonFormatted($vals);
        }

        if ($outputMode === self::OUTPUT_MODE_TO_FILE) {
            if (@file_put_contents(self::$file, $str) === false) {
                throw new RuntimeException(sprintf(
                    'Data could not be written to file %s.',
                    self::$file
                ));
            }
            self::dump(sprintf('Debug data successfully written to: %s.', self::$file));
        }

        exit($str);
    }

    /**
     * Set the output mode.
     *
     * @param  ?int $outputMode
     * @return void
     * @throws InvalidArgumentException
     */
    public static function setOutputMode($outputMode, $file = '/tmp/debug.txt')
    {
        if (!in_array($outputMode, [
            self::OUTPUT_MODE_STDOUT_FORMAT,
            self::OUTPUT_MODE_STDOUT_DO_NOT_FORMAT,
            self::OUTPUT_MODE_TO_FILE,
            null
        ])) {
            throw new InvalidArgumentException(
                'Debug mode must be %s::OUTPUT_MODE_STDOUT_FORMAT, '
                    . '%s::OUTPUT_MODE_STDOUT_DO_NOT_FORMAT, '
                    . 'or %s::OUTPUT_MODE_TO_FILE.',
                $classPath = get_class($this),
                $classPath,
                $classPath
            );
        }

        if ($outputMode === self::OUTPUT_MODE_TO_FILE) {
            if (!is_string($file)) {
                throw new InvalidArgumentException(sprintf(
                    '%s::setOutputMode second parameter must be a string, got %s.',
                    get_class($this),
                    self::formatType($val)
                ));
            }

            if (empty($file)) {
                throw new InvalidArgumentException(sprintf(
                    '%s::setOutputMode second parameter cannot be empty.',
                    get_class($this)
                ));
            }

            self::$file = $file;
        }

        self::$outputMode = $outputMode;
    }

    /**
     * Fetch the output mode.
     *
     * Returns the display mode, if no output mode is set, then it is set
     * by best guess and then returned.
     *
     * @return int
     */
    private static function fetchOutputMode()
    {
        if (self::$outputMode === null) {
            if (self::isCli() || self::isAjax()) {
                self::$outputMode = self::OUTPUT_MODE_STDOUT_DO_NOT_FORMAT;
            } else {
                self::$outputMode = self::OUTPUT_MODE_STDOUT_FORMAT;
            }
        }

        return self::$outputMode;
    }

    /**
     * Return true this is cli mode.
     *
     * @return bool
     */
    private static function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Returns true if the request is PROBABLY ajax.
     *
     * @return bool
     */
    private static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Returns a user friendly type name for the value passed in.
     *
     * @param  mixed $val
     * @return string
     */
    private static function formatType($val)
    {
        if (($type = gettype($val)) === 'object') {
            return sprintf('class: %s', get_class($val));
        }

        if ($type === 'double') {
            return 'float';
        }

        if ($type === 'NULL') {
            return 'null';
        }

        return $type;
    }

    /**
     * Prepare formatted data for dumping to stdOut, return the result as a string.
     *
     * @param  array $vals
     * @return string
     */
    private static function prepareFormatted(array $vals)
    {
        return sprintf('<pre>%s</pre>', implode("\n", array_map(function ($val) {
            return print_r(htmlspecialchars($val), true);
        }, $vals)));
    }

    /**
     * Prepare non formatted data for dumping to stdOut or writing to a temp file, return the result as a string.
     *
     * @param  array $vals
     * @return string
     */
    private static function prepareNonFormatted(array $vals)
    {
        return implode("\n", array_map(function ($val) {
            return print_r($val, true);
        }, $vals)) . "\n";
    }

    // Prevent substantiation of purely static class.
    final private function __construct()
    {
    }
}
