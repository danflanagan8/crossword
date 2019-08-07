(function ($, Drupal, drupalSettings) {

  Drupal.Crossword = function(data) {
    var Crossword = this;
    this.data = data;
    this.dir = 'across';
    this.activeSquare = {'row' : null, 'col': null};
    this.activeClue = null;
    this.answers = emptyAnswers();

    function emptyAnswers() {
      var grid = Crossword.data.puzzle.grid;
      var answers = [];
      for (var row_index = 0; row_index < grid.length; row_index++) {
        answers.push([]);
        for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
          answers[row_index].push(null);
        }
      }
      return answers;
    }

    this.setActiveSquare = function(row, col) {
      var grid = Crossword.data.puzzle.grid;
      if (row >= 0 && row < grid.length && col >= 0 && col < grid[row].length && grid[row][col].fill !== null) {
        this.activeSquare = {'row' : row, 'col': col};
        if (this.dir == 'across') {
          try {
            this.activeClue = this.data.puzzle.grid[row][col].across.index;
          }
          catch {
            this.activeClue = null;
          }
        }
        else {
          try {
            this.activeClue = this.data.puzzle.grid[row][col].down.index;
          }
          catch {
            this.activeClue = null;
          }
        }
      }
      return this;
    }

    this.setActiveClue = function(index) {
      if (this.dir == 'across') {
        if (index >= 0 && index < this.data.puzzle.clues.across.length) {
          this.activeClue = index;
          var numeral = this.data.puzzle.clues.across[index].numeral;
          var grid = this.data.puzzle.grid;
          for (var row_index = 0; row_index < grid.length; row_index++) {
            for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
              if (grid[row_index][col_index].numeral == numeral) {
                this.activeSquare = {
                  'row' : row_index,
                  'col' : col_index,
                };
                return this;
              }
            }
          }
        }
      }
      else {
        if (index >= 0 && index < this.data.puzzle.clues.down.length) {
          this.activeClue = index;
          var numeral = this.data.puzzle.clues.down[index].numeral;
          var grid = this.data.puzzle.grid;
          for (var row_index = 0; row_index < grid.length; row_index++) {
            for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
              if (grid[row_index][col_index].numeral == numeral) {
                this.activeSquare = {
                  'row' : row_index,
                  'col' : col_index,
                };
                return this;
              }
            }
          }
        }
      }
    }

    this.changeDir = function() {
      this.dir = this.dir == 'across' ? 'down' : 'across';
      this.setActiveSquare(this.activeSquare.row, this.activeSquare.col);
    }

    this.moveActiveSquare = function(rows, cols) {
      this.setActiveSquare(this.activeSquare.row + rows, this.activeSquare.col + cols);
      return this;
    }

    this.advanceActiveSquare = function() {
      if (this.dir == 'across') {
        this.moveActiveSquare(0, 1);
      }
      else {
        this.moveActiveSquare(1, 0);
      }
      return this;
    }

    this.retreatActiveSquare = function() {
      if (this.dir == 'across') {
        this.moveActiveSquare(0, -1);
      }
      else {
        this.moveActiveSquare(-1, 0);
      }
      return this;
    }

    this.advanceActiveClue = function() {
      this.setActiveClue(this.activeClue + 1);
      return this;
    }

    this.retreatActiveClue = function() {
      this.setActiveClue(this.activeClue - 1);
      return this;
    }

    this.setAnswer = function(letter) {
      this.answers[this.activeSquare.row][this.activeSquare.col] = letter;
    }

    this.setActiveClue(0);
  }

  Drupal.behaviors.crossword = {
    attach: function (context, settings) {
      $('body').once('crossword-init').each(function(){
        var data = drupalSettings.crossword;
        var Crossword = new Drupal.Crossword(data);
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
            Drupal.behaviors.crossword.printLetter(String.fromCharCode(event.which).toUpperCase(), Crossword);
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
          Drupal.behaviors.crossword.updateClasses(Crossword, false);
        });
      });
    },
    updateClasses: function (Crossword, focus) {
      $('.crossword-square.active, .crossword-clue.active').removeClass('active');
      $('.crossword-square.highlight').removeClass('highlight');
      var $activeSquare = $('.crossword-square[data-row="' + Crossword.activeSquare.row + '"][data-col="' + Crossword.activeSquare.col + '"]');
      $activeSquare.addClass('active');
      if (focus) {
        $activeSquare.find('input').focus;
      }

      if (Crossword.dir == 'across') {
        $('.crossword-clue[data-clue-index-across="' + Crossword.activeClue + '"]').addClass('active');
        $('.crossword-square[data-clue-index-across="' + Crossword.activeClue + '"]').addClass('highlight');
      }
      else {
        $('.crossword-clue[data-clue-index-down="' + Crossword.activeClue + '"]').addClass('active');
        $('.crossword-square[data-clue-index-down="' + Crossword.activeClue + '"]').addClass('highlight');
      }
    },
    printLetter: function (letter, Crossword){
      Crossword.setAnswer(letter);

      var $activeSquare = $('.crossword-square[data-row="' + Crossword.activeSquare.row + '"][data-col="' + Crossword.activeSquare.col + '"]');
      $activeSquare.find('.square-fill').text(letter);
      $activeSquare.removeClass('wrong');
      if (Crossword.data.puzzle.grid[Crossword.activeSquare.row][Crossword.activeSquare.col].fill !== letter) {
        $activeSquare.addClass('wrong');
      }

      if (letter == "") {
        Crossword.retreatActiveSquare();
      }
      else {
        Crossword.advanceActiveSquare();
      }

      Drupal.behaviors.crossword.updateClasses(Crossword, true);
    }
  }
})(jQuery, Drupal, drupalSettings);