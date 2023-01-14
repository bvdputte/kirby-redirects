<?php

require __DIR__ . DS . "classes" . DS . "Redirects.php";
require __DIR__ . DS . "classes" . DS . "autoRedirects.php";

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
            $redirector = bvdputte\redirects\AutoRedirects::singleton();

            // Do not manage redirects for drafts
            if($oldPage->status() != "draft") {
                // Add to redirects, or update the $toId when $from is already in redirects
                if (kirby()->multilang()) {
                    $langCode = kirby()->language()->code();
                    $from = $langCode . '/' . $oldPage->uri();
                    $redirector->addRedirect(
                        from: $from,
                        destId: $newPage->uuid()->id(),
                        code: option('bvdputte.redirects.autoredirectsDefaultCode'),
                        langCode: $langCode
                    );
                } else {
                    $redirector->addRedirect(
                        from: $oldPage->uri(),
                        destId: $newPage->uuid()->id(),
                        code: option('bvdputte.redirects.autoredirectsDefaultCode')
                    );
                }
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
            $redirector = bvdputte\redirects\AutoRedirects::singleton();
            if($newPage->status() == "draft") {
                // Remove all redirects pointing "to" this page
                $redirects = $redirector->getAllForDestination($newPage->uuid()->id());
                foreach($redirects as $redirectId => $redirect) {
                    $redirector->deleteRedirect($redirectId);
                }
            }
        },
        'page.delete:after' => function ($status, $page) {
            // Remove all redirects pointing "to" this page
            $redirector = bvdputte\redirects\AutoRedirects::singleton();
            $redirects = $redirector->getAllForDestination($page->uuid()->id());
            foreach($redirects as $redirectId => $redirect) {
                $redirector->deleteRedirect($redirectId);
            }
        },
        'page.create:before' => function ($page, $input) {
            // Avoid creating a page when there is already a redirect to its slug
            $redirector = bvdputte\redirects\AutoRedirects::singleton();

            // Handle multilang setups
            if (kirby()->multilang()) {
                // Page creations always occur in default language in Kirby
                $from = kirby()->defaultLanguage()->code() . '/' . $page->uri();

                // Check if there is a an existing redirect for this in the default language
                $results = $redirector->getAllForFrom($from);
                if(! empty($results)) {
                    $destPageId = $results[array_key_first($results)]['destId'];
                    if ($destPage = page('page://' . $destPageId)) {
                        throw new LogicException(
                            'There is already a redirect pointing to this URL at ' .
                            kirby()->defaultLanguage()->code() . '/' . $destPage->uri()
                        );
                    } else {
                        // Continue, but remove stale redirect
                        $redirector->deleteRedirect(array_key_first($results));
                    }
                }

                // Continue in the current language
                $langCode = kirby()->language()->code();
                $from = $langCode . '/' . $page->uri();

                // Check if there is a an existing redirect for this in the current language
                $results = $redirector->getAllForFrom($from);
                if(! empty($results)) {
                    $destPageId = $results[array_key_first($results)]['destId'];
                    if ($destPage = page('page://' . $destPageId)) {
                        throw new LogicException(
                            'There is already a redirect pointing to this URL at ' .
                            $langCode . '/' . $destPage->uri($langCode)
                        );
                    } else {
                        // Continue, but remove stale redirect
                        $redirector->deleteRedirect(array_key_first($results));
                    }
                }
            } else {
                // Non-multilang setup
                $from = $page->uri();

                // Check if there is a an existing redirect for this URI
                $results = $redirector->getAllForFrom($from);
                if(! empty($results)) {
                    $destPageId = $results[array_key_first($results)]['destId'];
                    if ($destPage = page('page://' . $destPageId)) {
                            throw new LogicException(
                                'There is already a redirect pointing to this URL at ' .
                                $destPage->uri()
                            );
                    } else {
                        // Continue, but remove stale redirect
                        $redirector->deleteRedirect(array_key_first($results));
                    }
                }
            }
        }
    ],
    'fields' => [
        'redirects' => [
            'props' => [
                'multilang' => kirby()->multilang(),
            ]
        ]
    ]
]);
