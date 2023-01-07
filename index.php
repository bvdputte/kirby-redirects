<?php

require __DIR__ . DS . "src" . DS . "Redirects.php";
require __DIR__ . DS . "src" . DS . "autoRedirects.php";

// For composer
@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use Kirby\Exception\Exception;

$kirbyVersion = App::version();
if (
    $kirbyVersion !== null &&
    (
        version_compare($kirbyVersion, '3.8.0', '<') === true
    )
) {
    throw new Exception(
        'The installed version of the Kirby Redirects plugin ' .
        'is not compatible with Kirby ' . $kirbyVersion
    );
}

// https://forum.getkirby.com/t/a-minimalist-redirect-solution-that-intercepts-404-errors/24007

Kirby::plugin('bvdputte/redirects', [
    'options' => [
        'redirectsFileRoot' => kirby()->root('config') . '/' . 'redirects.json',
        'autoredirectsFileRoot' => kirby()->root('config') . '/' . 'redirects-via-panel.json',
        'autoredirectsDefaultCode' => 302
    ],
    'routes' => require_once __DIR__ . '/routes.php',
    'hooks' => [
        'route:after' => function ($path, $result) {
            // Fallback to redirects if route has no result in Kirby
            if($result === null) {
                // Try to redirect from autoredirects.json
                bvdputte\redirects\AutoRedirects::singleton()->redirect($path);
                // Try to redirect from redirects.json
                bvdputte\redirects\Redirects::singleton()->redirect($path);
            }

            // Add log for failed redirects?
        },
        'page.changeSlug:after' => function ($newPage, $oldPage) {
            // Do not manage redirects for drafts
            if($oldPage->status() != "draft") {
                // Add updated slug to redirects file
                if (kirby()->multilang()) {
                    $from = kirby()->language()->code() . '/' . $oldPage->uri();
                } else {
                    $from = $oldPage->uri();
                }

                bvdputte\redirects\AutoRedirects::singleton()->add(
                    from: $from,
                    id: $newPage->uuid()->id(),
                    code: option('bvdputte.redirects.autoredirectsDefaultCode')
                );
            }
        },
        'page.changeSlug:before' => function ($page, $slug, $languageCode) {
            // Do not allow change slug for pages with subpages
            if ($page->childrenAndDrafts()->isNotEmpty()) {
                throw new LogicException('The slug cannot be changed because the page has subpages');
            }
        },
        'page.changeStatus:after' => function ($newPage, $oldPage) {
            // When page status changes back in "draft", remove all redirects pointing "to" this page
            if($newPage->status() == "draft") {
                // Remove all redirects pointing "to" this page
                bvdputte\redirects\AutoRedirects::singleton()->delete(id: $newPage->uuid()->id());
            }
        },
        'page.delete:after' => function ($status, $page) {
            // Remove all redirects pointing "to" this page
            bvdputte\redirects\AutoRedirects::singleton()->delete(id: $page->uuid()->id());
        },
        'page.create:before' => function ($page, $input) {
            // Avoid creating a page when there is already a redirect to its slug

            // Handle multilang setups
            if (kirby()->multilang()) {
                // Page creations always occur in default language in Kirby
                $from = kirby()->defaultLanguage()->code() . '/' . $page->uri();

                // Check if there is a an existing redirect for this in the default language
                $results = bvdputte\redirects\AutoRedirects::singleton()->getAllFrom($from);
                if(! empty($results)) {
                    $toPageId = $results[0][1];
                    if ($toPage = page('page://' . $toPageId)) {
                        throw new LogicException(
                            'There is already a redirect pointing to this URL at ' .
                            kirby()->defaultLanguage()->code() . '/' . $toPage->uri()
                        );
                    }
                }

                // Continue in the current language
                $langCode = kirby()->language()->code();
                $from = $langCode . '/' . $page->uri();

                // Check if there is a an existing redirect for this in the current language
                $results = bvdputte\redirects\AutoRedirects::singleton()->getAllFrom($from);
                if(! empty($results)) {
                    $toPageId = $results[0][1];
                    if ($toPage = page('page://' . $toPageId)) {
                        throw new LogicException(
                            'There is already a redirect pointing to this URL at ' .
                            $langCode . '/' . $toPage->uri($langCode)
                        );
                    } else {
                        // Continue, but remove stale redirect
                        bvdputte\redirects\AutoRedirects::singleton()->delete(
                            from: $from,
                            id: $toPageId
                        );
                    }
                }
            } else {
                // Non-multilang setup
                $from = $page->uri();

                // Check if there is a an existing redirect for this URI
                $results = bvdputte\redirects\AutoRedirects::singleton()->getAllFrom($from);
                if(! empty($results)) {
                    $toPageId = $results[0][1];
                    if ($toPage = page('page://' . $toPageId)) {
                            throw new LogicException(
                                'There is already a redirect pointing to this URL at ' .
                                $toPage->uri()
                            );
                    } else {
                        // Continue, but remove stale redirect
                        bvdputte\redirects\AutoRedirects::singleton()->delete(
                            from: $from,
                            id: $toPageId
                        );
                    }
                }
            }
        }
    ]
]);
