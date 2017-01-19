<?php

namespace Drupal\twig_food\TwigExtension;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\TwigExtension;

/**
 * @package Twig Food
 * @author Michal Landsman <michal.landsman@burda.cz>
 */
class Food extends \Twig_Extension
{
    /**
     * The renderer.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected $renderer;

    protected $twigExtension;

    /**
     * Constructs \Drupal\Core\Template\TwigExtension.
     *
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   The renderer.
     */
    public function __construct(RendererInterface $renderer)
    {
        $this->twigExtension = new TwigExtension($renderer);
    }

    /**
     * Gets a unique identifier for this Twig extension.
     */
    public function getName()
    {
        return 'twig_food.twig_extension';
    }

    /**
     * Generate a list of all twig functions
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('svg', array($this, 'renderSVG'), array('is_safe' => array('html')))
        );
    }

    /**
     * Generates a list of all Twig filters that this extension defines.
     */
    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('naked_field', array($this, 'renderNakedField')),
        );
    }

    /**
     * Return SVG source code as string to Twig - usage: {{ svg('bgCarousel.svg')|raw }}
     * @param $path
     * @return string
     */
    public function renderSVG($path)
    {
        $theme = drupal_get_path("theme", \Drupal::theme()->getActiveTheme()->getName());
        $fullPath = "{$theme}/images/{$path}";

        $handle = fopen($fullPath, "r");
        $contents = fread($handle, filesize($fullPath));
        fclose($handle);

        return $contents;
    }

    /**
     * Make render of var, removes html comments from string, do strip_tags, remove new lines => naked string
     * Example: A string which has value  <!-- Start DEBUG --> ABCD <!-- End DEBUG -->
     * will be returned the output ABCD after using the the following function.
     * @param string $string A string, which have html comments.
     * @return string A string, which have no html comments.
    */
    public function renderNakedField($string)
    {
        $rendered = $this->twigExtension->renderVar($string);
        $withoutComments = preg_replace('/<!--(.|\s)*?-->/', '', $rendered);
        $naked = strip_tags(str_replace(array("\n", "\r"), '', $withoutComments));

        return $naked;
    }
}