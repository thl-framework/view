<?php

namespace Mods\View;

use Layout\Core\PageFactory;
use Mods\Theme\Factory as ThemeFactory;

class Factory
{
    /**
     * @var \Layout\Core\PageFactory $pageFactory
     */
    protected $pageFactory;

    /**
     * @var \Mods\Theme\Factory $themeFactory
     */
    protected $themeFactory;

    /**
     *
     * @param  \Layout\Core\PageFactory  $pageFactory
     * @param  \Mods\Theme\Factory $themeFactory
     */
    public function __construct(PageFactory $pageFactory, ThemeFactory $themeFactory)
    {
        $this->pageFactory = $pageFactory;
        $this->themeFactory = $themeFactory;
    }

    /**
     * Render the current page and return view
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $html = $this->pageFactory->render();
        $html['head'] = $this->updateAssetUrls($html['head']);
        return view('root', $html);
    }

    /**
     * Get the page factory
     *
     * @return  \Layout\Core\Factory
     */
    public function getPageFactory()
    {
        return $this->pageFactory;
    }

    /**
     * Fix the base url for the assets and do the last mintue updates
     *
     * @return  array
     */
    protected function updateAssetUrls($head)
    {
        $area = app()->area();
        $theme = $this->themeFactory->getActiveTheme($area);
        $manifest = $this->fetchManifest($area, $theme);
        $routeHandler = $this->pageFactory->routeHandler();

        if (isset($manifest['bundled']) && $manifest['bundled']) {
            $head['js'] = '<script src="'.
                $this->getJsBaseUrl($area, $theme).'bundle/'.$routeHandler.
            '.js"></script>';
            $head['css'] = '<link href="'.
                $this->getCssBaseUrl($area, $theme).'bundle/'.$routeHandler.
            '.css" media="all" rel="stylesheet" />';
        } else {
            $minified = (isset($manifest['minified']) && $manifest['minified']);
            $minified = ($minified)?'min/':'';
            $head['js'] = str_replace(
                '%baseurl', $this->getJsBaseUrl($area, $theme).$minified,
                $head['js']
            );
            $head['css'] = str_replace(
                '%baseurl', $this->getCssBaseUrl($area, $theme).$minified,
                 $head['css']
            );
        }
        return $head;
    }

    /**
     * Get the base url for script
     *
     * @param string $area
     * @param string $theme
     * @return string
     */
    public function getJsBaseUrl($area, $theme)
    {
        return asset("assets/{$area}/{$theme}/js").'/';
    }

    /**
     * Get the base url for style
     *
     * @param string $area
     * @param string $theme
     * @return string
     */
    public function getCssBaseUrl($area, $theme)
    {
        return asset("assets/{$area}/{$theme}/css").'/';
    }

    /**
     * Get the base url for style
     *
     * @param string $area
     * @param string $theme
     * @return array
     */
    protected function fetchManifest($area, $theme)
    {
        $manifestPath = $this->getPath(
            [app('path.resources'), 'assets', $area, $theme, 'manifest.json']
        );
        if (!file_exists($manifestPath)) {
            return [];
        }
        return json_decode(file_get_contents($manifestPath), true);
    }

    protected function getPath($paths)
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}
