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
        foreach ($this->getAll() as $redirect) {
            if ($redirect['from'] == $path) {
                $page = page('page://'.$redirect['destId']);
                if ($page) {
                    $langCode = !is_null($redirect['langCode']) ? $redirect['langCode'] : kirby()->language()->code();
                    $code = isset($redirect['code']) ? $redirect['code'] : $this->options['autoredirectsDefaultCode'];

                    die(Response::redirect(
                        $page->url($langCode),
                        $code)
                    );
                }
            }
        }
    }

    public function addRedirect(string $from, string $destId, string $langCode = null, int $code = null)
    {
        // Only allow add when "destination" page exists
        if (page('page://' . $destId)) {
            $redirectCode = !is_null($code) ? $code : $this->options['autoredirectsDefaultCode'];

            // Avoid duplicate entries
            if ($this->getAllForFrom($from)) {
                // ray('Redirect already exists. Update it with the given params');
                foreach($this->getAllForFrom($from) as $key => $value) {
                    $redirects = $this->updateRedirect(
                        redirectId: $key,
                        from: $from,
                        destId: $destId,
                        code: $redirectCode,
                        langCode: $langCode
                    );
                }

                return $redirects;
            } else {
                // ray('Does not exist yet, adding redirect');
                $redirects = $this->getAll();
                $redirects[uniqid()] = [
                    'from' => $from,
                    'destId' => $destId,
                    'code' => $redirectCode,
                    'langCode' => $langCode
                ];

                $this->writeToFile($redirects);
                return $redirects;
            }
        }

        // ray('Page doesnt exist. Bailing out.');
        return false;
    }

    public function deleteRedirect(string $redirectId)
    {
        $redirects = $this->getAll();

        if (isset($redirects[$redirectId])) {
            unset($redirects[$redirectId]);

            $this->writeToFile($redirects);
            return $redirects;
        }

        return false;
    }

    public function updateRedirect(string $redirectId, string $from = null, string $destId = null, string $code = null)
    {
        $redirects = $this->getAll();

        if (isset($redirects[$redirectId])) {
            if (! is_null($from)) {
                $redirects[$redirectId]['from'] = $from;
            }
            if (! is_null($destId)) {
                $redirects[$redirectId]['destId'] = $destId;
            }
            if (! is_null($code)) {
                $redirects[$redirectId]['code'] = $code;
            }

            $this->writeToFile($redirects);
            return $redirects;
        }

        return false;
    }

    private function map()
    {
        return json_decode(F::read($this->options['redirectsfileRoot']) ?? [], true);
    }

    private function writeToFile($map)
    {
        F::write(
            $this->options['redirectsfileRoot'],
            json_encode($map,  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function getAllForFrom(string $from)
    {
        $map = $this->getAll();
        $keyedFromList = array_diff(array_combine(array_keys($map), array_column($map, 'from')), [null]);
        $results = array_keys($keyedFromList, $from);

        if(count($results) == 0) return false;

        $redirects = [];
        foreach($results as $redirectId) {
            $redirects[$redirectId] = $map[$redirectId];
        }

        return $redirects;
    }

    public function getAllForDestination(string $destId)
    {
        $map = $this->getAll();
        $keyedToList = array_diff(array_combine(array_keys($map), array_column($map, 'destId')), [null]);
        $results = array_keys($keyedToList, $destId);

        if(count($results) == 0) return false;

        $redirects = [];
        foreach($results as $redirectId) {
            $redirects[$redirectId] = $map[$redirectId];
        }

        return $redirects;
    }

    public function getAll()
    {
        return $this->map();
    }

    public function appendDestinationUri(array $redirects)
    {
        $updatedRedirects = array_map(function($item) {
            if ($toPage = page('page://' . $item['destId'])) {
                if (
                    (kirby()->multilang()) &&
                    (isset($item['langCode']))
                ) {
                    $langCode = $item['langCode'];
                    $toUrl = $langCode . '/' . $toPage->uri($langCode);
                } else {
                    $toUrl = $toPage->uri();
                }
                $item['to'] = $toUrl;

                return $item;
            } else {
                return false;
            }
        }, $redirects);

        return $updatedRedirects;
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
