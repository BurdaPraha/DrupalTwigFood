<?php

namespace Drupal\twig_food\TwigExtension;

use Drupal\Core\Entity\EntityTypeManagerInterface,
    Drupal\Core\Render\RendererInterface,
    Drupal\Core\Template\TwigExtension,
    Drupal\image\Entity\ImageStyle,
    Drupal\file\Entity\File,
    Drupal\media_entity\Entity\Media;

/**
 * @package Twig Food
 * @author Michal Landsman <michal.landsman@burda.cz>
 */
class Food extends \Twig_Extension
{
    /**
     * The entity type manager.
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var TwigExtension
     */
    protected $coreTwigExtension;

    /**
     * @var string
     */
    protected $themeName;

    /**
     * @param EntityTypeManagerInterface $entity_type_manager
     * @param RendererInterface $renderer
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer)
    {
        $this->entityTypeManager = $entity_type_manager;
        $this->coreTwigExtension = new TwigExtension($renderer);
        $this->themeName         = \Drupal::theme()->getActiveTheme()->getName();
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
        return [
            new \Twig_SimpleFunction('svg',                 [$this, 'renderSVG'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('load_block',          [$this, 'loadBlock']),
            new \Twig_SimpleFunction('load_region',         [$this, 'loadRegion']),
            new \Twig_SimpleFunction('get_main_node',       [$this, 'getMainNode']),
            new \Twig_SimpleFunction('load_gallery_prev',   [$this, 'loadGalleryPrev']),
            new \Twig_SimpleFunction('load_gallery_next',   [$this, 'loadGalleryNext']),
            new \Twig_SimpleFunction('load_gallery_thumbs', [$this, 'loadGalleryThumbs']),
        ];
    }

    /**
     * Generates a list of all Twig filters that this extension defines.
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('naked_field', [$this, 'renderNakedField']),
            new \Twig_SimpleFilter('max_length',  [$this, 'renderWithMaxLength']),
        ];
    }

    /**
     * Return SVG source code as string to Twig - usage: {{ svg('bgCarousel.svg')|raw }}
     * @param $path
     * @return string
     */
    public function renderSVG($path)
    {
        $theme      = drupal_get_path("theme", $this->themeName);
        $fullPath   = "{$theme}/images/{$path}";
        $handle     = fopen($fullPath, "r");
        $contents   = fread($handle, filesize($fullPath));
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
        $rendered        = $this->coreTwigExtension->renderVar($string);
        $withoutComments = preg_replace('/<!--(.|\s)*?-->/', '', $rendered);
        $naked           = strip_tags(str_replace(["\n", "\r"], '', $withoutComments));

        return $naked;
    }

    /**
     * Check string length and return him summary or in original
     * @param $string
     * @param int $max Max. length of string
     * @param bool|true $dots add "..." after summary string
     * @return string
     */
    public function renderWithMaxLength($string, $max = 0, $dots = true)
    {
        $field = $this->renderNakedField($string);

        if(strlen($field) > $max && $max > 0)
        {
            $break  = "*-*-*";
            $wrap   = wordwrap($field, $max, $break);
            $items  = explode($break, $wrap);
            $string = (isset($items[0]) ? $items[0] : "") . ($dots ? "..." : "");
        }

        return $string;
    }

    /**
     * Return array of selected block
     * @param $id string
     * @return array|string
     */
    public function loadBlock($id)
    {
        $block = $this->entityTypeManager->getStorage('block')->load($id);
        return $block ? $this->entityTypeManager->getViewBuilder('block')->view($block) : '';
    }

    /**
     * Render region by id
     * @param $id
     * @return array
     */
    public function loadRegion($id)
    {
        $blocks = $this->entityTypeManager->getStorage('block')->loadByProperties([
            'region' => $id,
            'theme'  => $this->themeName
        ]);

        $result = [];
        foreach($blocks as $id => $values)
        {
            $result[] = $this->loadBlock($id);
        }

        return $result;
    }

    /**
     * Prev gallery
     * @param $id
     * @param string $thumbnail
     * @return array|null
     */
    public function loadGalleryPrev($id, $thumbnail = 'thumbnail')
    {
        return $this->getMediaData($id, '<', 'DESC', $thumbnail);
    }

    /**
     * Next gallery
     * @param $id
     * @param string $thumbnail
     * @return array|null
     */
    public function loadGalleryNext($id, $thumbnail = 'thumbnail')
    {
        return $this->getMediaData($id, '>', 'ASC', $thumbnail);
    }

    /**
     * Load gallery images
     * @param $id
     * @param string $thumbnail
     * @return array
     */
    public function loadGalleryThumbs($id, $thumbnail = 'thumbnail')
    {
        $gallery = $this->entityTypeManager
                        ->getStorage('media')
                        ->load($id);

        $images = $gallery->get('field_media_images');

        if($images)
        {
            $result = [];
            foreach($images as $image)
            {
                $mid        = $image->entity->id();
                $fileEntity = $image->entity->field_image->entity;
                $fid        = $image->entity->field_image->entity->id();
                $imageUrl   = $fileEntity->getFileUri();

                $result[] = [
                    'mid'   => $mid,
                    'fid'   => $fid,
                    'thumb' => ImageStyle::load($thumbnail)->buildUrl($imageUrl),
                ];
            }

            return $result;
        }

        return [];
    }

    /**
     * Load main node object anywhere
     * @param bool|true $returnId
     * @return mixed|null
     */
    public function getMainNode($returnId = true)
    {
        $node = \Drupal::routeMatch()->getParameter('node');
        if ($node)
        {
            return $returnId ? $node->id() : $node;
        }

        return null;
    }

    /**
     * Load one gallery
     * @param $currentId
     * @param $dateComparator
     * @param $sortOrder
     * @param $thumbnail
     * @return array|null
     */
    public function getMediaData($currentId, $dateComparator, $sortOrder, $thumbnail)
    {
        /**
         * @var \Drupal\media_entity\Entity\Media
         */
        $current = $this->entityTypeManager
                        ->getStorage('media')
                        ->load($currentId);

        if(!$current)
        {
            return null;
        }

        $prev_or_next = \Drupal::entityQuery('media')
            ->condition('bundle', $current->bundle())
            ->condition('status', 1)
            ->condition('created', $current->getCreatedTime(), $dateComparator)
            ->sort('created', $sortOrder)
            ->range(0, 1)
            ->execute();

        if(!$prev_or_next)
        {
            return null;
        }

        $gallery = $this->entityTypeManager
            ->getStorage('media')
            ->load(array_values($prev_or_next)[0]);

        $all    = $gallery->get('field_media_images');
        $file   = $all[0]->entity->field_image->entity->getFileUri();

        return [
            'id'        => $gallery->id(),
            'title'     => $gallery->label(),
            'path'      => $gallery->toUrl()->toString(),
            'images'    => $all,
            'thumb'     => ImageStyle::load($thumbnail)->buildUrl($file)
        ];
    }

}