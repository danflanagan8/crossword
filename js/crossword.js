(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.crossword = {
    attach: function (context, settings) {
      $('.crossword').once('crossword-init').each(function(){
        var $crossword = $(this);

        var data = drupalSettings.crossword;
        var Crossword = new Drupal.Crossword.Crossword(data);

        $crossword.data("Crossword", Crossword);

        Drupal.behaviors.crossword.connectSquares($crossword);
        Drupal.behaviors.crossword.connectClues($crossword);
        Drupal.behaviors.crossword.addClickHandlers($crossword);
        Drupal.behaviors.crossword.addCrosswordEventHandlers($crossword);

        //keyboard event listeners.
        addEventListener("keydown", function(event) {
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

        addEventListener("keypress", function(event) {
          //printable characters
          if(event.which){
            //letter key
            event.preventDefault();
            Crossword.setAnswer(String.fromCharCode(event.which));
            Crossword.focus();
          }
        });

        $('#show-errors').once('crossword-show-errors-change').on('change', function(){
          $('.crossword').toggleClass('show-errors');
        });

        $('#show-references').once('crossword-show-references-change').on('change', function(){
          $('.crossword').toggleClass('show-references');
        }).prop('checked', true);

      });
    },
    connectSquares: function ($crossword) {
      $('.crossword-square', $crossword).each(function(){
        var row = Number($(this).data('row'));
        var col = Number($(this).data('col'));
        $(this).data("Square", $crossword.data("Crossword").grid[row][col]);
        $(this).data("Square")['$square'] = $(this);
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
        $(this).data("Clue")["$clue"] = $(this);
      });
    },
    addClickHandlers: function ($crossword) {
      var Crossword = $crossword.data("Crossword");
      $('.crossword-square', $crossword).once('crossword-square-click').click(function(){
        if ($(this).data("Square") == Crossword.activeSquare) {
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

      $('.cheat').once('crossword-cheat-click').click(function(e){
        e.preventDefault();
        Crossword.cheat();
      });

      $('.show-solution').once('crossword-show-solution-click').click(function(e){
        e.preventDefault();
        Crossword.reveal();
      });

    },
    addCrosswordEventHandlers: function ($crossword) {
      $('.crossword-clue, .crossword-square', $crossword)
        .on('crossword-active', function(){
          $(this).addClass('active');
        })
        .on('crossword-highlight ', function(){
          $(this).addClass('highlight');
        })
        .on('crossword-reference', function(){
          $(this).addClass('reference');
        })
        .on('crossword-off', function(){
          $(this)
            .removeClass('active')
            .removeClass('highlight')
            .removeClass('reference')
            .find('input').blur();
        })
        .on('crossword-cheat', function(){
          $(this).addClass('cheat');
        });

      $('.crossword-square', $crossword)
        .on('crossword-answer', function(e, answer){
          $(this).find('.square-fill').text(answer.toUpperCase());
        })
        .on('crossword-error', function(){
          $(this).addClass('error');
        })
        .on('crossword-ok', function(){
          $(this).removeClass('error');
        })
        .on('crossword-rebus', function(){
          $(this).addClass('rebus');
        })
        .on('crossword-not-rebus', function(){
          $(this).removeClass('rebus');
        })
        .on('crossword-focus', function(){
          $(this).find('input').focus();
        });
    },
  }
})(jQuery, Drupal, drupalSettings);
