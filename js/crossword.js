(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.crossword = {
    attach: function (context, settings) {
      var selector = drupalSettings.crossword.selector;
      $(selector).once('crossword-init').each(function(){
        var $crossword = $(this);

        var data = drupalSettings.crossword.data;
        var answers = Drupal.behaviors.crossword.loadAnswers(data);
        var Crossword = new Drupal.Crossword.Crossword(data, answers);

        $crossword.data("Crossword", Crossword);

        Drupal.behaviors.crossword.addCrosswordEventHandlers($crossword);
        Drupal.behaviors.crossword.connectClues($crossword);
        Drupal.behaviors.crossword.connectSquares($crossword);
        Drupal.behaviors.crossword.addKeydownHandlers($crossword);
        Drupal.behaviors.crossword.addKeypressHandlers($crossword);
        Drupal.behaviors.crossword.addClickHandlers($crossword);

        // Trick the display into updating now that everything is connected.
        Crossword.setActiveClue(Crossword.activeClue);

        // Some stuff for the checkboxes that might as well be here.
        $('#show-errors').once('crossword-show-errors-change').on('change', function(){
          $('.crossword').toggleClass('show-errors');
        });

        $('#show-references').once('crossword-show-references-change').on('change', function(){
          $('.crossword').toggleClass('show-references');
        }).prop('checked', true);

      });
    },
    loadAnswers: function (data) {
      if (localStorage.getItem(data.title) !== null) {
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
    addKeydownHandlers: function($crossword) {
      var Crossword = $crossword.data("Crossword");

      $(document).on("keydown", function(event) {
        //for arrows, spacebar, and tab
        switch(event.keyCode) {
          case 38:
            //up
            event.preventDefault();
            Crossword.moveActiveSquare('up');
            break;
          case 37:
            //left
            event.preventDefault();
            Crossword.moveActiveSquare('left');
            break;
          case 39:
            //right
            event.preventDefault();
            Crossword.moveActiveSquare('right');
            break;
          case 40:
            //down
            event.preventDefault();
            Crossword.moveActiveSquare('down');
            break;
          case 32:
            //spacebar
            event.preventDefault();
            Crossword.changeDir();
            break;
          case 9:
            //tab
            event.preventDefault();
            if (event.shiftKey) {
              Crossword.retreatActiveClue();
            }
            else {
              Crossword.advanceActiveClue();
            }
            break;
            //backspace
          case 46:
          case 8:
            Crossword.setAnswer("");
            Crossword.focus();
        }
      });
    },
    addKeypressHandlers: function($crossword) {
      var Crossword = $crossword.data("Crossword");

      $(document).on("keypress", function(event) {
        //printable characters
        if(event.which){
          //letter key
          event.preventDefault();
          Crossword.setAnswer(String.fromCharCode(event.which));
          Crossword.focus();
        }
      });
    },
    addClickHandlers: function ($crossword) {
      var Crossword = $crossword.data("Crossword");

      $('.crossword-square', $crossword).once('crossword-square-click').click(function(){
        if ($(this).data("Square") == Crossword.activeSquare && $(this).hasClass('focus')) {
          Crossword.changeDir();
        }
        else {
          Crossword.setActiveSquare($(this).data("Square"));
        }
        Crossword.focus();
      });

      $('.crossword-clue', $crossword).once('crossword-clue-click').click(function(){
        Crossword.setActiveClue($(this).data("Clue"));
        Crossword.focus();
      });

      $('.crossword-clue-change', $crossword).once('crossword-clue-change-click').click(function(e){
        e.preventDefault();
        var dir = $(this).data('dir');
        var change = Number($(this).data('clue-change'));
        Crossword.changeActiveClue(dir, change);
      });

      $('.crossword-dir-change', $crossword).once('crossword-dir-change-click').click(function(e){
        e.preventDefault();
        var dir = $(this).data('dir');
        if (dir != Crossword.dir) {
          Crossword.changeDir();
        }
      });

      $('.button-cheat').once('crossword-cheat-click').click(function(e){
        e.preventDefault();
        Crossword.cheat();
      });

      $('.button-undo').once('crossword-undo-click').click(function(e){
        e.preventDefault();
        Crossword.undo();
      });

      $('.button-redo').once('crossword-redo-click').click(function(e){
        e.preventDefault();
        Crossword.redo();
      });

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
        .on('crossword-focus', function(){
          $(this).addClass('focus');
          $(this).find('input').focus();
        });

      $('.crossword-clue', $crossword)
        .on('crossword-active', function(){
          $('.active-clues', $crossword).html('<div class="active ' + $(this).data("Clue").dir + '">' + $(this).html() + '</div>');
        })
        .on('crossword-reference', function(){
          $('.active-clues', $crossword).append('<div class="reference ' + $(this).data("Clue").dir + '">' + $(this).html() + '</div>');
        })
        .on('crossword-off', function(){
          $('.active-clues', $crossword).html(null);
        });
    },
  }
})(jQuery, Drupal, drupalSettings);
