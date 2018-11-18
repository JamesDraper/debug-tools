# Debug Tools

A simple debug tools library.

## Installation

To install this library, clone this repository and ensure that `Debug.php`
is included in any/all requests that you wish to debug.

## Usage

All debug methods are static methods on the `Debug` object. By default, the
object will try to guess whether this is a HTTP request or not. If it is
a HTTP request then it will format any out output it produces as preformatted
text. If it is not then it will not format the output.

## Methods

   Debug::onError(callable $callback): void

This method takes a callback which is called when the request is finished
no matter what. It passes into that request the type, message, file, and line
of the last fatal error. It can be useful for catching fatal errors in applications
running on older versions of PHP.

    Debug::watch(callable $callback): void
    Debug::startWatch(): void

These methods are useful if some kind of error must be caught in some code,
but it does not occur on the first iteration. The 'watch' method takes a
callable. Each time the `watch` is reached it checks to see if the `startWatch`
method has been called yet. If it has, then the callback is executed. If it has
not then nothing happens.

    Debug::startTimer(): void
    Debug::stopTimer(): void

Useful for measuring the execution time of something in microseconds. First
call `startTimer`, then call `stopTimer`. Once `stopTimer` is called the
elapsed time in microseconds is printed.

    Debug::dumpObjectMethods(mixed $object): void

Prints an array of all the methods an object has out through the terminal.

    Debug::dumpType(mixed $object): void

Prints out a neat, human readable name for the object type.

    Debug::dumpClassFilePath(mixed $object): void

Prints out the path under which a class was found.

    Debug::dumpTrace(): void

Prints out a human readable stack trace detailing how
this point in the code was reached.

    Debug::dump($value): void

Prints out the value.

    Debug::setOutputMode(int $mode, string $file = '/tmp/debug.txt'): void

This method can take one of three values:

- Debug::OUTPUT_MODE_STDOUT_FORMAT
- Debug::OUTPUT_MODE_STDOUT_DO_NOT_FORMAT
- Debug::OUTPUT_MODE_TO_FILE

By default, the debug class tries to guess which output mode it should use.
If it believes that something is a Non-ajax HTTP request it will output
anything it dumps as preformatted text (`<pre>`). If it is not then it will not
format the output.

Optionally it may also output to a file. If `Debug::OUTPUT_MODE_TO_FILE` is used
then afilepath must be passed into `Debug::setOutputMode' as the second argument.
After that, any output will be dumped into that file.
