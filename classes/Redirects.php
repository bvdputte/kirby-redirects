<?php

namespace bvdputte\redirects;

use Kirby\Cms\Response;
use Kirby\Toolkit\A;
use Kirby\Toolkit\F;

class Redirects
{
    private $options;

    public function __construct(array $options = [])
    {
        $defaults = [
            'redirectsfileRoot' => option('bvdputte.redirects.redirectsFileRoot'),
            'autoredirectsDefaultCode' => option('bvdputte.redirects.autoredirectsDefaultCode')
        ];
        $this->options = A::merge($defaults, $options);
    }

    public function redirect(string $path)
    {
        // Try to find a redirect in the manual redirects json file
        foreach ($this->map() as $redirect) {
            if (preg_match('#' . $redirect[0] . '#', $path, $matches)) {
                $target = preg_replace('#' . $redirect[0] . '#', $redirect[1], $path);

                die(Response::redirect(
                    $target,
                    $redirect[2] ?? $this->options['autoredirectsDefaultCode'])
                );
            }
        }
    }

    private function map()
    {
        return json_decode(F::read($this->options['redirectsfileRoot']) ?? []);
    }

    private static $singleton;

    public static function singleton($options = [])
    {
        if (! self::$singleton) {
            self::$singleton = new self($options);
        }

        return self::$singleton;
    }
}
