<?php

namespace bvdputte\redirects;

use Kirby\Cms\Response;
use Kirby\Toolkit\A;
use Kirby\Toolkit\F;

class AutoRedirects
{
    private $options;

    public function __construct(array $options = [])
    {
        $defaults = [
            'redirectsfileRoot' => option('bvdputte.redirects.autoredirectsFileRoot'),
            'autoredirectsDefaultCode' => option('bvdputte.redirects.autoredirectsDefaultCode')
        ];
        $this->options = A::merge($defaults, $options);
    }

    public function redirect(string $path)
    {
        // Try to find a redirect in the automated redirects json file
        foreach ($this->map() as $redirect) {
            if ($redirect[0] == $path) {
                $page = page('page://'.$redirect[1]);
                if ($page) {
                    $langCode = kirby()->language()->code();
                    $code = isset($redirect[2]) ? $redirect[2] : $this->options['autoredirectsDefaultCode'];

                    die(Response::redirect(
                        $page->url($langCode),
                        $code)
                    );
                }
            }
        }
    }

    public function add(string $from, string $id, int $code)
    {
        // Avoid duplicate entries
        if ($this->getAllFrom($from)) {
            // ray('Redirect already exists. Replace the $to & $code.');
            $redirects = $this->replaceFrom($from, $id, $code);
        } else {
            // ray('Does not exist yet, adding redirect');
            $redirects = $this->map();
            if (page('page://' . $id)) {
                $redirects[] = [
                    $from,
                    $id,
                    $code
                ];
            } else {
                // ray('Page doesnt exist. Bailing out.');
                return false;
            }
        }

        $this->writeToFile($redirects);
        return $redirects;
    }

    public function delete($from = null, $id = null)
    {
        $redirects = $this->map();
        // ray('Try to delete ' . $from . ' ...');
        if (!is_null($from)) {
            if ($indexes = $this->getAllFromIndexes($from)) {
                foreach($indexes as $index) {
                    unset($redirects[$index]);
                    // ray('Deleted ' . $from);
                }
            } else {
                // ray($from . ' not found');
            }
        }

        if (!is_null($id)) {
            if ($indexes = $this->getAllToIndexes($id)) {
                foreach($indexes as $index) {
                    unset($redirects[$index]);
                    // ray('Deleted ' . $id);
                }
            } else {
                // ray($id . ' not found');
            }
        }

        $this->writeToFile(array_values($redirects));
        return $redirects;
    }

    public function replaceFrom(string $from, string $id, int $code)
    {
        $map = $this->map();
        $indexes = $this->getAllFromIndexes($from);

        foreach($indexes as $index) {
            $map[$index][1] = $id;
            $map[$index][2] = $code;
        }

        return $map;
    }

    public function getAllFrom(string $from)
    {
        $map = $this->map();
        $results = $this->getAllFromIndexes($from);

        if(count($results) == 0) return false;

        $redirects = [];
        foreach ($results as $index) {
            $redirects[] = $map[$index];
        }
        return $redirects;
    }

    public function getAllTo(string $id)
    {
        $map = $this->map();
        $results = $this->getAllToIndexes($id);

        if(count($results) == 0) return false;

        $redirects = [];
        foreach ($results as $index) {
            $redirects[] = $map[$index];
        }
        return $redirects;
    }

    private function map()
    {
        return json_decode(F::read($this->options['redirectsfileRoot']) ?? []);
    }

    private function writeToFile($map)
    {
        F::write(
            $this->options['redirectsfileRoot'],
            json_encode($map,  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function getAllFromIndexes(string $from)
    {
        $map = $this->map();
        $results = array_keys(array_column($map, 0), $from);

        return $results;
    }

    private function getAllToIndexes(string $id)
    {
        $map = $this->map();
        $results = array_keys(array_column($map, 1), $id);

        return $results;
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
