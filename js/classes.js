(function ($, Drupal, drupalSettings) {

  Drupal.Crossword = {
    Crossword: function(data) {
      var Crossword = this;
      this.data = data;

      this.dir = 'across';
      this.activeSquare = {'row' : null, 'col': null};
      this.activeClue = null;
      this.activeReferences = [];
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
            catch(error) {
              this.activeClue = null;
            }
          }
          else {
            try {
              this.activeClue = this.data.puzzle.grid[row][col].down.index;
            }
            catch(error) {
              this.activeClue = null;
            }
          }
          this.setActiveReferences();
        }
        return this;
      }

      this.setActiveClue = function(index) {
        if (this.dir == 'across') {
          if (index >= 0 && index < this.data.puzzle.clues.across.length) {
            this.activeClue = index;
            this.setActiveReferences();
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
            this.setActiveReferences();
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

      this.setActiveReferences = function() {
        if (this.dir == 'across') {
          this.activeReferences = this.data.puzzle.clues.across[this.activeClue].references;
        }
        else {
          this.activeReferences = this.data.puzzle.clues.down[this.activeClue].references;
        }
      }

      this.setAnswer = function(letter) {
        this.answers[this.activeSquare.row][this.activeSquare.col] = letter;
      }

      this.setActiveClue(0);
    }
  }

})(jQuery, Drupal, drupalSettings);
