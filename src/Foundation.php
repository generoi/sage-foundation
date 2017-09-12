<?php

namespace Genero\Sage;

use Timber\Twig_Function;

class Foundation
{
    /** @var array */
    public $config;

    /**
     * Foundation constructor
     *
     * @param array $config Foundation configurations.
     */
    public function __construct($config)
    {
        $this->config = array_replace_recursive([
            'color' => [
                'primary'   => __('Primary', 'sage-foundation'),
                'secondary' => __('Secondary', 'sage-foundation'),
            ],
            'palette' => [
                'button' => ['primary', 'secondary'],
                'callout' => ['primary', 'secondary', 'success', 'warning', 'alert'],
            ],
            'breakpoint' => [
                'small'   => 0,
                'medium'  => 640,
                'large'   => 1024,
                'xlarge'  => 1200,
                'xxlarge' => 1440,
            ],
            'fontsize' => [
                'small' => 16,
            ],
            'paragraph_width' => 45,

            'grid' => 'xy-grid',
        ], $config);

        add_filter('widget-options-extended/grid', [$this, 'getGridType'], 9);
        add_filter('tailor-foundation/grid', [$this, 'getGridType'], 9);
        add_filter('tiny_mce_before_init', [$this, 'tinymceFormats'], 9);
        add_filter('sage/timber/class/post_cell', [$this, 'postCellClass'], 9);
        add_filter('sage/timber/class/post_grid', [$this, 'postGridClass'], 9);
    }

    /**
     * Get a CSS class for cells in a grid.
     *
     * @param array $classes
     * @return array
     */
    public function postCellClass($classes)
    {
        $classes[] = $this->config('grid') === 'xy-grid' ? 'cell' : 'column';
        return $classes;
    }

    /**
     * Set CSS classes for post grid listings.
     *
     * @param array $classes
     * @return string
     */
    public function postGridClass($classes)
    {
        if ($this->config('grid') === 'xy-grid') {
            $classes = array_merge($classes, [
                'grid-x',
                'grid-margin-x',
                'grid-margin-y',
                'align-center',
                'align-stretch',
                'small-up-1',
                'medium-up-2',
                'large-up-3',
            ]);
        } else {
            $classes[] = 'row';
        }

        $classes[] = 'small-up-1';
        $classes[] = 'medium-up-2';
        $classes[] = 'large-up-3';

        return $classes;
    }

    /**
     * Add Foundation formats to TinyMCE.
     *
     * @param array $settings
     * @return array;
     */
    public function tinymceFormats($settings)
    {
        // Button formats
        $buttons[] = ['title' => 'Buttons', 'selector' => 'a', 'classes' => 'button'];
        foreach ($this->palette('button') as $class => $name) {
            $buttons[] = ['title' => sprintf('%s Color (Button)', $name), 'selector' => 'a.button', 'classes' => $class];
        }
        $buttons[] = ['title' => 'Tiny (Button)', 'selector' => 'a.button', 'classes' => 'tiny'];
        $buttons[] = ['title' => 'Small (Button)', 'selector' => 'a.button', 'classes' => 'small'];
        $buttons[] = ['title' => 'Large (Button)', 'selector' => 'a.button', 'classes' => 'large'];

        // Callout formats
        $callouts[] = ['title' => 'Callout box', 'block' => 'div', 'classes' => 'callout', 'wrapper' => true];
        foreach ($this->palette('callout') as $class => $name) {
            $callouts[] = ['title' => sprintf('%s (Callout)', $name), 'selector' => 'div.callout', 'classes' => $class];
        }

        $style_formats = [
            ['title' => 'Buttons', 'items' => $buttons],
            ['title' => 'Callout', 'items' => $callouts],
        ];

        $settings['style_formats'] = json_encode($style_formats);
        $settings['style_formats_merge'] = true;

        return $settings;
    }

    public function getGridType()
    {
        return $this->config('grid');
    }

    /**
     * Get/set a config value
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function config($key, $value = null)
    {
        if (isset($value)) {
            $this->config[$key] = $value;
        }
        return $this->config[$key];
    }

    /**
     * Register twig functions and filters.
     *
     * @param Twig_Environment $twig.
     */
    public function registerTwig($twig)
    {
        $foundation = $this;

        // Return the value of a breakpoint in pixels without the unit.
        $twig->addFunction(new Twig_Function('foundation_breakpoint', function ($breakpoint) use ($foundation) {
            return $foundation->breakpoint($breakpoint);
        }));
        // Get a sizes attribute value based on a string of Foundation grid classes.
        $twig->addFunction(new Twig_Function('foundation_sizes', function ($classes) use ($foundation) {
            return $foundation->parseSizes($classes);
        }));
    }

    /**
     * Get a option list of the available palette colors the theme has.
     *
     * @param string $type
     * @return array
     */
    public function palette($type = 'all')
    {
        $colors = $this->config('color');
        if ($type === 'all') {
            return $colors;
        }

        $palette = $this->config('palette');
        if (isset($palette[$type])) {
            return array_intersect_key($colors, array_combine($palette[$type], $palette[$type]));
        }
        return $colors;
    }

    /**
     * Get a Foundation media breakpoint in pixels.
     *
     * @param string $name
     * @return int|array
     */
    public function breakpoint($name = null)
    {
        $breakpoints = $this->config('breakpoint');
        return isset($name) ? $breakpoints[$name] : $breakpoints;
    }

    /**
     * Get the font size for the specified breakpoint in pixels.
     *
     * @param string $breakpoint
     * @return int|array
     */
    public function fontsize($breakpoint = 'small')
    {
        $fontsizes = $this->config('fontsize');

        foreach ($fontsizes as $_breakpoint => $fontsize) {
            if ($breakpoint == $_breakpoint) {
                return $fontsize;
            }
            // If `small` and `large` are configured, `medium` should return `large`
            if ($this->breakpoint($_breakpoint) > $this->breakpoint($breakpoint)) {
                return $fontsize;
            }
        }
    }

    /**
     * Get the length of a paragraph as a CSS value including it's unit.
     *
     * @param string $breakpoint
     * @return string
     */
    public function paragraphWidth($breakpoint = 'small')
    {
        $max_width = ($this->config('paragraph_width') * $this->fontsize($breakpoint));
        $breakpoints = $this->breakpoint();
        // Advance until the requested breakpoint
        while (key($breakpoints) !== $breakpoint) {
            next($breakpoints);
        }

        // Check if this breakpoint spans beyond the max width.
        if (next($breakpoints) > $max_width) {
            return $max_width . 'px';
        }

        // Return approximate viewport based width.
        return '95vw';
    }

    /**
     * Get a sizes attribute value bases on Foundation grid classes.
     *
     * @param string $classes A string of classes such as `medium-4 large-8`
     * @return string
     */
    public function parseSizes($classes)
    {
        $sizes = [];
        foreach (explode(' ', $classes) as $class_name) {
            $dash_position = strrpos($class_name, '-');
            $breakpoint = substr($class_name, 0, $dash_position);
            $cells = substr($class_name, $dash_position + 1);

            $ratio = round(($cells/12) * 100);

            $width = $this->breakpoint($breakpoint);
            if ($width === 0) {
                $sizes[$breakpoint] = "{$ratio}vw";
            } else {
                $sizes[$breakpoint] = "(min-width: {$width}px) {$ratio}vw";
            }
        }

        if (!isset($sizes['small'])) {
            $sizes['small'] = '100vw';
        }

        $result = [];
        foreach (array_reverse($this->breakpoint(), true) as $breakpoint => $size) {
            if (!empty($sizes[$breakpoint])) {
                $result[] = $sizes[$breakpoint];
            }
        }

        return implode(', ', $result);
    }
}
