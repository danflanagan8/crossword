(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.crosswordScreenreader = {
    attach: function (context, settings) {
      var selector = drupalSettings.crossword.selector;
      $(selector).once('crossword-init').each(function(){
        var $crossword = $(this);

        var data = drupalSettings.crossword.data;
        var answers = Drupal.behaviors.crosswordScreenreader.loadAnswers(data);
        var Crossword = new Drupal.Crossword.Crossword(data, answers);
        Crossword.$crossword = $crossword;
        $crossword.data("Crossword", Crossword);

        Drupal.behaviors.crosswordScreenreader.addCrosswordEventHandlers($crossword);
        Drupal.behaviors.crosswordScreenreader.connectClues($crossword);
        Drupal.behaviors.crosswordScreenreader.connectSquares($crossword);
        Drupal.behaviors.crosswordScreenreader.addInputHandlers($crossword);
        Drupal.behaviors.crosswordScreenreader.addClickHandlers($crossword);

      });
    },
    loadAnswers: function (data) {
      if (localStorage.getItem(data.id) !== null) {
        return JSON.parse(localStorage.getItem(data.id));
      }
      else {
        var emptyAnswers = Drupal.behaviors.crosswordScreenreader.emptyAnswers(data);
        localStorage.setItem(data.id, JSON.stringify(emptyAnswers));
        return emptyAnswers;
      }
    },
    emptyAnswers: function (data) {
      var grid = data.puzzle.grid;
      var answers = [];
      for (var row_index = 0; row_index < grid.length; row_index++) {
        answers.push([]);
        for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
          answers[row_index].push(null);
        }
      }
      return answers;
    },
    connectSquares: function ($crossword) {
      $('.crossword-square', $crossword).each(function(){
        var row = Number($(this).data('row'));
        var col = Number($(this).data('col'));
        $(this).data("Square", $crossword.data("Crossword").grid[row][col]);
        $(this).data("Square").connect($(this));
      });
    },
    connectClues: function ($crossword) {
      $('.crossword-clue', $crossword).each(function(){
        if ($(this).data('clue-index-across') !== undefined) {
          var index = Number($(this).data('clue-index-across'));
          $(this).data("Clue", $crossword.data("Crossword").clues.across[index]);
        }
        else {
          var index = Number($(this).data('clue-index-down'));
          $(this).data("Clue", $crossword.data("Crossword").clues.down[index]);
        }
        $(this).data("Clue").connect($(this));
      });
    },
    addInputHandlers: function($crossword) {
      var Crossword = $crossword.data("Crossword");
      $('.crossword-clue', $crossword).on('submit', function(e){
        e.preventDefault();
        var $input = $(this).find("input[type='text']");
        var Clue = $(this).data("Clue");
        Crossword.setActiveClue(Clue);
        Crossword.setClueResponse(Clue, $input.val());
      });

      $('.crossword-clue input[type="text"]', $crossword).on('blur', function(e){
        e.preventDefault();
        $(this).val("");
      })
      .on('focus', function(e){
        e.preventDefault();
        $(this).val("");
      });

      $('#crossword-blank-count', $crossword).on('focus', function(){
        $(this).attr('aria-label', "There are " + Crossword.countBlankSquares() + " blank squares. Hit return to go back to the first clue.");
      })
      .click(function(){
        $('.crossword-clues.across input[type="text"]', $crossword).first().focus();
      });
    },
    addClickHandlers: function ($crossword) {
      var Crossword = $crossword.data("Crossword");

      $('.button-solution').once('crossword-solution-click').click(function(e){
        e.preventDefault();
        Crossword.reveal();
      });

      $('.button-clear').once('crossword-clear-click').click(function(e){
        e.preventDefault();
        Crossword.clear();
      });

    },
    addCrosswordEventHandlers: function ($crossword) {

      $('.crossword-square', $crossword)
        .on('crossword-answer', function(e, answer){
          $(this).find('.square-fill').text(answer.toUpperCase());
          var Crossword = $crossword.data("Crossword");
          localStorage.setItem(Crossword.id, JSON.stringify(Crossword.getAnswers()));
        })
        .on('crossword-rebus', function(){
          $(this).addClass('rebus');
        })
        .on('crossword-not-rebus', function(){
          $(this).removeClass('rebus');
        })

      $('.crossword-clue', $crossword)
        .on('crossword-aria-update', function(e){
          var Clue = $(this).data("Clue");
          $(this).find('label').attr("aria-label", Clue.getAriaClueText() + ". " + Clue.getAriaCurrentString());
        });

      $crossword.on('crossword-solved', function() {
        console.log('The crossword puzzle has been solved.');
        $('#solution-message').attr('aria-hidden', false).attr('hidden', false).focus();
      });
    },
  }
})(jQuery, Drupal, drupalSettings);
