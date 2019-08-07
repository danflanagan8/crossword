(function ($, Drupal, drupalSettings) {

  Drupal.Crossword = function(data) {
    var Crossword = this;
    this.data = data;
    this.dir = 'down';
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

      return this;
    }

    this.setActiveClue = function(index) {
      this.activeClue = index;
      if (this.dir == 'across') {
        var numeral = this.data.puzzle.clues.across[index].numeral;
        var grid = this.data.puzzle.grid;
        console.log(grid);
        for (var row_index = 0; row_index < grid.length; row_index++) {
          for (var col_index = 0; col_index < grid[row_index].length; col_index++) {
            console.log(grid[row_index][col_index].numeral);
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
      else {
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

    this.changeDir = function() {
      this.dir = this.dir == 'across' ? 'down' : 'across';
      this.setActiveSquare(this.activeSquare.row, this.activeSquare.col);
    }

    this.setActiveClue(0);
  }

  Drupal.behaviors.crossword = {
    attach: function (context, settings) {

      var data = drupalSettings.crossword;
      var Crossword = new Drupal.Crossword(data);

      $('.crossword-square').once('crossword-square-click').click(function(){

        if ($(this).hasClass('active')) {
          Crossword.changeDir();
        }
        else {
          var row = Number($(this).data('row'));
          var col = Number($(this).data('col'));
          Crossword.setActiveSquare(row, col);
        }

        Drupal.behaviors.crossword.updateClasses(Crossword);
        console.log(Crossword);
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
        Drupal.behaviors.crossword.updateClasses(Crossword);
      });
    },
    updateClasses: function (Crossword) {
      $('.crossword-square.active, .crossword-clue.active').removeClass('active');
      $('.crossword-square.highlight').removeClass('highlight');
      $('.crossword-square[data-row="' + Crossword.activeSquare.row + '"][data-col="' + Crossword.activeSquare.col + '"]').addClass('active');

      if (Crossword.dir == 'across') {
        $('.crossword-clue[data-clue-index-across="' + Crossword.activeClue + '"]').addClass('active');
        $('.crossword-square[data-clue-index-across="' + Crossword.activeClue + '"]').addClass('highlight');
      }
      else {
        $('.crossword-clue[data-clue-index-down="' + Crossword.activeClue + '"]').addClass('active');
        $('.crossword-square[data-clue-index-down="' + Crossword.activeClue + '"]').addClass('highlight');
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
