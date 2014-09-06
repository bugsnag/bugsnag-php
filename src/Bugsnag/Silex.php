<?php

/* The Silex PHP Framework Middleware
 *
 * Example application:
 *
 * use Symfony\Component\HttpFoundation\Request;
 *
 * require_once __DIR__.'/vendor/autoload.php';
 *
 * $bugsnag = new Bugsnag_Client('066f5ad3590596f9aa8d601ea89af845');
 * set_error_handler(array($bugsnag, 'errorhandler'));
 * set_exception_handler(array($bugsnag, 'exceptionhandler'));
 *
 * $app = new Silex\Application();
 *
 * $app->before(Bugsnag_Silex::beforeMiddleware());
 * $app->error(Bugsnag_Silex::errorMiddleware($bugsnag));
 *
 * $app->get('/hello/{name}', function($name) use($app) {
 *   throw new Exception("Hello!");
 *   return 'Hello '.$app->escape($name);
 * });
 *
 * $app->run();
 */
class Bugsnag_Silex
{
    private static $request;

    /* Captures request information */
    public static function beforeMiddleware()
    {
        $middlewareFunc = function (Request $request) {
            self::$request = $request;
        };
        return $middlewareFunc;
    }

    /* Filters stack frames and appends new tabs */
    public static function errorMiddleware($client)
    {
        return function (Exception $error, $code) use($client) {
            $client->setBeforeNotifyFunction(function (Bugsnag_Error $e) {
                $frames = array_filter($e->stacktrace->frames, function ($frame) {
                    $file = $frame['file'];

                    if (preg_match('/^\[internal\]/', $file)) {
                        return FALSE;
                    }

                    if (preg_match('/symfony\/http-kernel/', $file)) {
                        return FALSE;
                    }

                    if (preg_match('/silex\/silex\//', $file)) {
                        return FALSE;
                    }

                    return TRUE;
                });

                $e->stacktrace->frames = array();
                foreach ($frames as $frame) {
                    $e->stacktrace->frames[] = $frame;
                }

                $e->setMetaData(array(
                    "user" => array(
                        "clientIp" => self::$request->getClientIp()
                    )
                ));
            });

            $session = self::$request->getSession();
            if ($session) {
                $session = $session->all();
            }

            $qs = array();
            parse_str(self::$request->getQueryString(), $qs);

            $client->notifyException($error, array(
                "request" => array(
                    "clientIp" => self::$request->getClientIp(),
                    "params" => $qs,
                    "requestFormat" => self::$request->getRequestFormat(),
                ),
                "session" => $session,
                "cookies" => self::$request->cookies->all(),
                "host" => array(
                    "hostanme" => self::$request->getHttpHost()
                )
            ));
        };
    }
}
