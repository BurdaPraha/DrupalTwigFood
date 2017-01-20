# DrupalTwigFood
Useful functions, filters for twig @ Drupal 8

## Instalation
Recomended using is via Composer:
`composer require burdapraha/drupal_twig_food`
... and install module "Burda Twig Food" in administration (domain.tld/admin/modules)

## Documentation

The best documentation is easy examples, right? :-)

### Functions

#### svg($path)
offering comfortably using svg images in templates, example: ```{{ svg('awesome_icon.svg') }}``` when file is stored in "/your-theme-name/images/awesome_icon.svg" and source code of svg will be printed to page as is. This solution is quick and you can use CSS features like ".your-div svg {fill: red}" etc.

------

### Filters

#### naked_field
return rendered field, for example from view, without developers suggestions (<!-- Hook: etc --->), without HTML tags like `<a href="xy">your_filed</a>`. Just naked string what you can use as class, data attribute or in twig condition! Using example: `{% set badge = content.field_show_badge|naked_field %}`

#### max_length(20, false)
Check string length and return him summary or in original. It is pretty alternative to ugly ```{{ teaser_text|length > 90 ? teaser_text|slice(0, 90) ~ ' ...' : teaser_text }}```. By second parameter you can disable adding "..." to the end of string.