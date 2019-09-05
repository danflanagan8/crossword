(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.crossword = {
    attach: function (context, settings) {
      var selector = drupalSettings.crossword.selector;
      $(selector).once('crossword-init').each(function(){
        var $crossword = $(this);

        var data = drupalSettings.crossword.data;
        var answers = Drupal.behaviors.crossword.loadAnswers(data);
        var Crossword = new Drupal.Crossword.Crossword(data, answers);
        Crossword.$crossword = $crossword;
        $crossword.data("Crossword", Crossword);

        Drupal.behaviors.crossword.addCrosswordEventHandlers($crossword);
        Drupal.behaviors.crossword.connectClues($crossword);
        Drupal.behaviors.crossword.connectSquares($crossword);
        Drupal.behaviors.crossword.addInputHandlers($crossword);
        Drupal.behaviors.crossword.addClickHandlers($crossword);

        // Trick the display into updating now that everything is connected.
        Crossword.setActiveClue(Crossword.activeClue);

        // Some stuff for the checkboxes that might as well be here.
        $('#show-errors', $crossword).once('crossword-show-errors-change').on('change', function(){
          $crossword.toggleClass('show-errors');
        });

        $('#show-references', $crossword).once('crossword-show-references-change').on('change', function(){
          $crossword.toggleClass('show-references');
        })

      });
    },
    loadAnswers: function (data) {
      if (localStorage.getItem(data.id) !== null) {
        return JSON.parse(localStorage.getItem(data.id));
      }
      else {
        var emptyAnswers = Drupal.behaviors.crossword.emptyAnswers(data);
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
        Crossword.setClueResponse($input.val());
        $input.val("");
      });

      $('.crossword-clue input[type="text"]', $crossword).on('blur', function(e){
        e.preventDefault();
        $(this).val("");
      })
      .on('focus', function(e){
        e.preventDefault();
        Crossword.setActiveClue($(this).closest('.crossword-clue').data("Clue"));
      });
    },
    addClickHandlers: function ($crossword) {
      var Crossword = $crossword.data("Crossword");

      $('.button-solution').once('crossword-solution-click').click(function(e){
        e.preventDefault();
        if (confirm('Do you really want to give up?')) {
          Crossword.reveal();
        }
      });

      $('.button-clear').once('crossword-clear-click').click(function(e){
        e.preventDefault();
        if (confirm('Do you really want to clear? This action cannot be undone.')){
          Crossword.clear();
        }
      });

    },
    addCrosswordEventHandlers: function ($crossword) {
      $('.crossword-clue, .crossword-square', $crossword)
        .on('crossword-active', function(){
          $(this).addClass('active');
        })
        .on('crossword-highlight', function(){
          $(this).addClass('highlight');
        })
        .on('crossword-reference', function(){
          $(this).addClass('reference');
        })
        .on('crossword-error', function(){
          $(this).addClass('error');
        })
        .on('crossword-ok', function(){
          $(this).removeClass('error');
        })
        .on('crossword-off', function(){
          $(this)
            .removeClass('active')
            .removeClass('highlight')
            .removeClass('reference')
            .removeClass('focus')
            .find('input').blur();
        })
        .on('crossword-cheat', function(){
          $(this).addClass('cheat');
        });

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
        .on('crossword-focus', function(){
          $(this).addClass('focus');
          $(this).find('input[type="text"]').focus();
        })
        .on('crossword-aria-update', function(e){
          var aria = $(this).find('label').attr("aria-label");
          aria = aria.substring(0, aria.indexOf("Current response:") + 17);
          $(this).find('label').attr("aria-label", aria + $(this).data("Clue").getAriaCurrentString());
        });

      $crossword.on('crossword-solved', function() {
        console.log('The crossword puzzle has been solved.');
      });
    },
  }
})(jQuery, Drupal, drupalSettings);
