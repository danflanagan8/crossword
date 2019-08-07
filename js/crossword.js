(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.crossword = {
    attach: function (context, settings) {
      console.log(drupalSettings.crossword);
    }
  }
})(jQuery, Drupal, drupalSettings);
