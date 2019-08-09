(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.crossword = {
    attach: function (context, settings) {
      $('body').once('crossword-init').each(function(){
        var data = drupalSettings.crossword;
        console.log(data);
        var Crossword = new Drupal.Crossword.Crossword(data);
        Drupal.behaviors.crossword.updateClasses(Crossword, false);
        //keyboard event listeners.
        addEventListener("keydown", function(event) {
          //for arrows, spacebar, and tab
          switch(event.keyCode) {
            case 38:
              //up
              event.preventDefault();
              Crossword.moveActiveSquare(-1, 0);
              Drupal.behaviors.crossword.updateClasses(Crossword, true);
              break;
            case 37:
              //left
              event.preventDefault();
              Crossword.moveActiveSquare(0, -1);
              Drupal.behaviors.crossword.updateClasses(Crossword, true);
              break;
            case 39:
              //right
              event.preventDefault();
              Crossword.moveActiveSquare(0, 1);
              Drupal.behaviors.crossword.updateClasses(Crossword, true);
              break;
            case 40:
              //down
              event.preventDefault();
              Crossword.moveActiveSquare(1, 0);
              Drupal.behaviors.crossword.updateClasses(Crossword, true);
              break;
            case 32:
              //spacebar
              event.preventDefault();
              Crossword.changeDir();
              Drupal.behaviors.crossword.updateClasses(Crossword, true);
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
              Drupal.behaviors.crossword.updateClasses(Crossword, true);
            case 46:
            case 8:
              Drupal.behaviors.crossword.printLetter("", Crossword);
              Drupal.behaviors.crossword.updateClasses(Crossword, true);
          }

        });

        addEventListener("keypress", function(event) {
          //printable characters
          if(event.which){
            //letter key
            event.preventDefault();
            console.log(event.which);
            Drupal.behaviors.crossword.printLetter(String.fromCharCode(event.which), Crossword);
          }
        });

        $('.crossword-square').once('crossword-square-click').click(function(){

          if ($(this).hasClass('active')) {
            Crossword.changeDir();
          }
          else {
            var row = Number($(this).data('row'));
            var col = Number($(this).data('col'));
            Crossword.setActiveSquare(row, col);
          }

          Drupal.behaviors.crossword.updateClasses(Crossword, true);

        });

        $('.crossword-clue').once('crossword-clue-click').click(function(){
          if ($(this).data('clue-index-across') !== undefined) {
            Crossword.dir = 'across';
            Crossword.setActiveClue($(this).data('clue-index-across'));
          }
          else {
            Crossword.dir = 'down';
            Crossword.setActiveClue($(this).data('clue-index-down'));
          }
          Drupal.behaviors.crossword.updateClasses(Crossword, true);
        });

        $('.crossword-clue-change').once('crossword-clue-change-click').click(function(e){
          e.preventDefault();
          var dir = $(this).data('dir');
          var change = Number($(this).data('clue-change'));
          if (dir == Crossword.dir) {
            Crossword.setActiveClue(Crossword.activeClue + change);
          }
          else {
            Crossword.dir = dir;
            Crossword.setActiveClue(0);
          }
          Drupal.behaviors.crossword.updateClasses(Crossword, false);
        });

        $('.crossword-dir-change').once('crossword-dir-change-click').click(function(e){
          e.preventDefault();
          var dir = $(this).data('dir');
          if (dir != Crossword.dir) {
            Crossword.changeDir();
            Drupal.behaviors.crossword.updateClasses(Crossword, false);
          }
        });

        $('.show-solution').once('crossword-show-solution-click').click(function(e){
          e.preventDefault();
          Drupal.behaviors.crossword.showSolution();
        });

        $('.cheat').once('crossword-show-solution-click').click(function(e){
          e.preventDefault();
          Drupal.behaviors.crossword.cheat(Crossword);
        });

        $('#show-errors').once('crossword-show-errors-change').on('change', function(){
          $('.crossword').toggleClass('show-errors');
        });

        $('#show-references').once('crossword-show-references-change').on('change', function(){
          $('.crossword').toggleClass('show-references');
        }).prop('checked', true);

      });
    },
    updateClasses: function (Crossword, focus) {
      $('.crossword-square, .crossword-clue').removeClass('active').removeClass('highlight').removeClass('reference');
      var $activeSquare = $('.crossword-square[data-row="' + Crossword.activeSquare.row + '"][data-col="' + Crossword.activeSquare.col + '"]');
      $activeSquare.addClass('active');
      if (focus) {
        console.log('focus!');
        $activeSquare.find('input').focus();
      }

      if (Crossword.dir == 'across') {
        var $activeClue = $('.crossword-clue[data-clue-index-across="' + Crossword.activeClue + '"]');
        $activeClue.addClass('active');
        $('.active-clues').html('<div class="across">' + $activeClue.html() + '</div>');
        $('.crossword-square[data-clue-index-across="' + Crossword.activeClue + '"]').addClass('highlight');
      }
      else {
        var $activeClue = $('.crossword-clue[data-clue-index-down="' + Crossword.activeClue + '"]');
        $activeClue.addClass('active');
        $('.active-clues').html('<div class="down">' + $activeClue.html() + '</div>');
        $('.crossword-square[data-clue-index-down="' + Crossword.activeClue + '"]').addClass('highlight');
      }

      // references!
      if (Crossword.activeReferences) {
        if (Crossword.activeReferences.across) {
          for (var i = 0; i < Crossword.activeReferences.across.index.length; i++) {
            var $referencedClue = $('.crossword-clue[data-clue-index-across="' + Crossword.activeReferences.across.index[i] + '"]');
            $referencedClue.addClass('reference');
            $('.active-clues').append('<div class="reference across">' + $referencedClue.html() + '</div>');
            $('.crossword-square[data-clue-index-across="' + Crossword.activeReferences.across.index[i] + '"]').addClass('reference');
          }
        }
        if (Crossword.activeReferences.down) {
          for (var i = 0; i < Crossword.activeReferences.down.index.length; i++) {
            var $referencedClue = $('.crossword-clue[data-clue-index-down="' + Crossword.activeReferences.down.index[i] + '"]');
            $referencedClue.addClass('reference');
            $('.active-clues').append('<div class="reference down">' + $referencedClue.html() + '</div>');
            $('.crossword-square[data-clue-index-down="' + Crossword.activeReferences.down.index[i] + '"]').addClass('reference');
          }
        }
      }
    },
    cheat: function(Crossword) {
      var $activeSquare = $('.crossword-square[data-row="' + Crossword.activeSquare.row + '"][data-col="' + Crossword.activeSquare.col + '"]');
      $activeSquare.removeClass('error').removeClass('rebus');
      $activeSquare.find('.square-fill').text($activeSquare.data('fill'));
      if($activeSquare.data('fill').length > 1) {
        $activeSquare.addClass('rebus');
      }
      Crossword.setAnswer($(this).data('fill'));
    },
    printLetter: function (letter, Crossword){

      var $activeSquare = $('.crossword-square[data-row="' + Crossword.activeSquare.row + '"][data-col="' + Crossword.activeSquare.col + '"]');
      $activeSquare.removeClass('error').removeClass('rebus');

      if (letter != letter.toLowerCase()) {
        // uppercase letters are used for rebus puzzles.
        // append this to what's already there
        $activeSquare.addClass('rebus');
        var current_text = $activeSquare.find('.square-fill').text();
        // is it all uppercase already? If so, append
        if (current_text != current_text.toLowerCase()) {
          $activeSquare.find('.square-fill').text(current_text + letter);
          Crossword.setAnswer(current_text + letter);
        }
        else {
          $activeSquare.find('.square-fill').text(letter);
          Crossword.setAnswer(letter);
        }
        if (Crossword.data.puzzle.grid[Crossword.activeSquare.row][Crossword.activeSquare.col].fill.toUpperCase() !== $activeSquare.find('.square-fill').text().toUpperCase()) {
          $activeSquare.addClass('error');
        }
      }
      else {
        Crossword.setAnswer(letter);

        var $activeSquare = $('.crossword-square[data-row="' + Crossword.activeSquare.row + '"][data-col="' + Crossword.activeSquare.col + '"]');
        $activeSquare.find('.square-fill').text(letter.toUpperCase());

        if (Crossword.data.puzzle.grid[Crossword.activeSquare.row][Crossword.activeSquare.col].fill.toUpperCase() !== letter.toUpperCase()) {
          $activeSquare.addClass('error');
        }

        if (letter == "") {
          Crossword.retreatActiveSquare();
        }
        else {
          Crossword.advanceActiveSquare();
        }
      }

      Drupal.behaviors.crossword.updateClasses(Crossword, true);
    },
    showSolution: function () {
      $('.crossword-square').each(function(){
        var fill = $(this).data('fill');
        $(this).find('.square-fill').text(fill);
        if (fill && fill.length > 1) {
          $(this).addClass('rebus');
        }
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
